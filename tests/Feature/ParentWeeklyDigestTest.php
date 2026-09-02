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
use App\Services\Analytics\ParentAnalyticsService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        (new DispatchWeeklyParentDigestJob)->handle(app(ParentAnalyticsService::class));

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

        (new DispatchWeeklyParentDigestJob)->handle(app(ParentAnalyticsService::class));

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

    public function test_concurrent_digest_workers_only_dispatch_once_per_parent_week(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::fake();
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedWeeklyActivity($parent, $student, xpEarned: 40);
        $parent->updatePushSubscription(
            'https://push.example.test/subscription/concurrent',
            'test-public-key',
            'test-auth-token',
        );

        $analytics = app(ParentAnalyticsService::class);
        $jobA = new DispatchWeeklyParentDigestJob;
        $jobB = new DispatchWeeklyParentDigestJob;

        DB::connection()->transaction(function () use ($jobA, $jobB, $analytics): void {
            $jobA->handle($analytics);
            $jobB->handle($analytics);
        });

        Mail::assertQueued(WeeklySummaryMailable::class, 1);
        Notification::assertSentTimes(WeeklySummaryWebPushNotification::class, 1);

        $this->assertSame(
            1,
            ParentReportLog::query()
                ->where('parent_id', $parent->id)
                ->where('report_type', 'weekly_digest')
                ->where('student_id', $student->id)
                ->count(),
        );

        Carbon::setTestNow();
    }

    public function test_webpush_failure_for_one_parent_does_not_block_remaining_digest_batch(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::fake();
        Log::spy();

        $parentA = User::factory()->create(['email' => 'parent-a@example.test']);
        $studentA = Student::factory()->for($parentA)->create();
        $this->seedWeeklyActivity($parentA, $studentA, xpEarned: 20);
        $parentA->updatePushSubscription(
            'https://push.example.test/expired-endpoint',
            'expired-public-key',
            'expired-auth-token',
        );

        $parentB = User::factory()->create(['email' => 'parent-b@example.test']);
        $studentB = Student::factory()->for($parentB)->create();
        $this->seedWeeklyActivity($parentB, $studentB, xpEarned: 35);
        $parentB->updatePushSubscription(
            'https://push.example.test/valid-endpoint',
            'valid-public-key',
            'valid-auth-token',
        );

        $this->app->bind(WebPushChannel::class, fn () => new class
        {
            public function send(object $notifiable, object $notification): void
            {
                $subscriptions = $notifiable->routeNotificationForWebPush();
                $endpoint = $subscriptions->first()?->endpoint;

                if (is_string($endpoint) && str_contains($endpoint, 'expired-endpoint')) {
                    throw new \RuntimeException('410 Gone: push subscription expired');
                }
            }
        });

        (new DispatchWeeklyParentDigestJob)->handle(app(ParentAnalyticsService::class));

        Mail::assertQueued(WeeklySummaryMailable::class, 2);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://push.example.test/expired-endpoint',
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://push.example.test/valid-endpoint',
        ]);

        $this->assertDatabaseHas('parent_report_logs', [
            'parent_id' => $parentA->id,
            'report_type' => 'weekly_digest',
        ]);
        $this->assertDatabaseHas('parent_report_logs', [
            'parent_id' => $parentB->id,
            'report_type' => 'weekly_digest',
        ]);

        Log::shouldHaveReceived('warning')->atLeast()->once();

        Carbon::setTestNow();
    }

    public function test_zero_activity_weekly_summary_renders_arabic_encouragement_without_errors(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');
        app()->setLocale('ar');

        $parent = User::factory()->create(['name' => 'ولي الأمر']);
        $student = Student::factory()->for($parent)->create([
            'name' => 'سارة',
            'grade_level' => 2,
        ]);

        $digest = [
            'period_start' => now()->copy()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'children' => [
                [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'grade_level' => $student->grade_level,
                    'xp_earned' => 0,
                    'quiz_accuracy_percent' => 0,
                    'activities_completed' => 0,
                    'analytics_url' => route('parent.analytics.show', $student),
                ],
            ],
        ];

        $html = (new WeeklySummaryMailable($parent, $digest))->render();

        $this->assertStringContainsString('سارة', $html);
        $this->assertStringContainsString('0', $html);
        $this->assertStringContainsString('لم يكمل أي أنشطة هذا الأسبوع، تشجيعه يفرق معه!', $html);

        $notification = new WeeklySummaryWebPushNotification($digest);
        $message = $notification->toWebPush($parent, $notification);
        $payload = $message->toArray();

        $this->assertSame(
            'لم يكمل أي أنشطة هذا الأسبوع، تشجيعه يفرق معه!',
            $payload['body'] ?? null,
        );

        Carbon::setTestNow();
    }

    public function test_digest_job_chunking_keeps_memory_under_budget_for_large_parent_batches(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::fake();
        Notification::fake();

        $parentRows = [];
        $studentRows = [];
        $now = now();

        for ($index = 0; $index < 500; $index++) {
            $parentId = $index + 1;
            $parentRows[] = [
                'id' => $parentId,
                'name' => 'Parent '.$index,
                'email' => "parent{$index}@example.test",
                'password' => bcrypt('password'),
                'role' => 'parent',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $studentRows[] = [
                'user_id' => $parentId,
                'name' => 'Student '.$index,
                'grade_level' => ($index % 5) + 1,
                'school_term' => 1,
                'total_xp' => 0,
                'coins' => 0,
                'lives' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($parentRows, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        foreach (array_chunk($studentRows, 100) as $chunk) {
            DB::table('students')->insert($chunk);
        }

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }

        $baseline = memory_get_usage(true);

        (new DispatchWeeklyParentDigestJob)->handle(app(ParentAnalyticsService::class));

        $peakDelta = memory_get_peak_usage(true) - $baseline;

        Mail::assertQueued(WeeklySummaryMailable::class, 500);

        $this->assertLessThan(
            64 * 1024 * 1024,
            $peakDelta,
            sprintf('Digest job exceeded 64MB memory budget (used %d bytes).', $peakDelta),
        );

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
