<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Jobs\DispatchWeeklyParentDigestJob;
use App\Mail\WeeklySummaryMailable;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\LearningMaterial;
use App\Models\ParentMaterial;
use App\Models\ParentReportLog;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\User;
use App\Notifications\WeeklySummaryWebPushNotification;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class ParentWeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_weekly_digest_job_compiles_progress_and_dispatches_email_and_webpush(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::fake();
        Notification::fake();
        Queue::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 3,
        ]);

        $this->seedWeeklyActivity($parent, $student, xpEarned: 40);
        $this->seedWeeklyQuiz($student, correctAnswers: 4, totalQuestions: 5, xpEarned: 30);

        $parent->updatePushSubscription(
            'https://push.example.test/subscription/weekly-digest',
            'test-public-key',
            'test-auth-token',
        );

        (new DispatchWeeklyParentDigestJob)->handle(app(\App\Services\Analytics\ParentAnalyticsService::class));

        Mail::assertQueued(
            WeeklySummaryMailable::class,
            fn (WeeklySummaryMailable $mailable): bool => $mailable->hasTo($parent->email)
                && collect($mailable->digest['children'])->contains(
                    fn (array $child): bool => $child['student_id'] === $student->id
                        && $child['xp_earned'] === 70
                        && $child['quiz_accuracy_percent'] === 80,
                ),
        );

        Notification::assertSentTo(
            $parent,
            WeeklySummaryWebPushNotification::class,
            fn (WeeklySummaryWebPushNotification $notification, array $channels): bool => in_array(WebPushChannel::class, $channels, true),
        );

        $this->assertDatabaseHas('parent_report_logs', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'report_type' => 'weekly_digest',
        ]);

        Carbon::setTestNow();
    }

    public function test_digest_job_is_idempotent_and_skips_already_sent_parents_for_current_week(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::fake();
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedWeeklyActivity($parent, $student, xpEarned: 25);

        $periodStart = now()->copy()->subDays(7)->startOfDay()->toDateString();
        $periodEnd = now()->copy()->endOfDay()->toDateString();

        ParentReportLog::query()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'report_type' => 'weekly_digest',
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
        ]);

        (new DispatchWeeklyParentDigestJob)->handle(app(\App\Services\Analytics\ParentAnalyticsService::class));

        Mail::assertNothingQueued();
        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_weekly_summary_mailable_renders_valid_arabic_html_with_deep_link(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        $parent = User::factory()->create(['name' => 'والدة ليان']);
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 3,
        ]);

        $analyticsUrl = route('parent.analytics.show', $student);

        $digest = [
            'period_start' => now()->copy()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'children' => [
                [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'grade_level' => $student->grade_level,
                    'xp_earned' => 120,
                    'quiz_accuracy_percent' => 85,
                    'activities_completed' => 2,
                    'analytics_url' => $analyticsUrl,
                ],
            ],
        ];

        $html = (new WeeklySummaryMailable($parent, $digest))->render();

        $this->assertStringContainsString('ليان', $html);
        $this->assertStringContainsString('120', $html);
        $this->assertStringContainsString('85', $html);
        $this->assertStringContainsString($analyticsUrl, $html);
        $this->assertStringContainsString('Tajawal', $html);

        Carbon::setTestNow();
    }

    private function seedWeeklyActivity(User $parent, Student $student, int $xpEarned): void
    {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'نشاط أسبوعي',
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()
            ->for($student)
            ->for($material, 'parentMaterial')
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => $xpEarned,
            ]);

        ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => 4,
                'total_questions' => 5,
                'xp_earned' => $xpEarned,
                'completed_at' => now()->subDays(2),
            ]);
    }

    private function seedWeeklyQuiz(
        Student $student,
        int $correctAnswers,
        int $totalQuestions,
        int $xpEarned,
    ): void {
        $material = LearningMaterial::factory()->published()->create(['title' => 'اختبار أسبوعي']);

        StudentQuizAttempt::factory()->for($student)->create([
            'learning_material_id' => $material->id,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score_percentage' => round(($correctAnswers / $totalQuestions) * 100, 2),
            'xp_earned' => $xpEarned,
            'completed_at' => now()->subDay(),
        ]);
    }
}
