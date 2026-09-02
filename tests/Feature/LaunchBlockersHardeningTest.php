<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\DispatchWeeklyParentDigestJob;
use App\Models\Badge;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\User;
use App\Services\Gamification\BadgeEvaluatorService;
use App\Services\Gamification\LeaderboardService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LaunchBlockersHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_user_role_cannot_be_mass_assigned_via_fill(): void
    {
        $user = User::factory()->create();

        $user->update(['role' => UserRole::Admin->value]);

        $this->assertSame(UserRole::Parent, $user->fresh()->role);
    }

    public function test_student_export_returns_valid_pdf_binary_stream(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'PDF Student',
            'total_xp' => 90,
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'pdf',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_repeat_quiz_attempts_do_not_unlock_quiz_count_badges(): void
    {
        Badge::query()->updateOrCreate(['code' => 'quiz_master_five'], [
            'name_ar' => 'خبير الاختبارات',
            'description_ar' => 'إكمال 5 اختبارات مختلفة',
            'icon' => 'medal',
            'criteria_type' => 'quiz_count',
            'criteria_value' => 5,
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 40]);
        $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
        $correct = QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->create(['order_column' => 1]);

        $answers = [[
            'question_id' => $question->id,
            'selected_option_id' => $correct->id,
        ]];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.materials.quiz.submit', $material), [
                    'answers' => $answers,
                ])
                ->assertOk();
        }

        $this->assertSame(5, $student->quizAttempts()->count());
        $this->assertFalse($student->fresh()->badges()->where('code', 'quiz_master_five')->exists());
        $this->assertTrue($student->fresh()->badges()->where('code', 'first_quiz')->exists());
        $this->assertSame(0, app(BadgeEvaluatorService::class)->evaluate($student->fresh())->count());
    }

    public function test_rank_for_student_executes_in_bounded_query_count(): void
    {
        Carbon::setTestNow('2026-01-07 12:00:00');

        $parent = User::factory()->create();
        $students = Student::factory()
            ->for($parent)
            ->count(50)
            ->create([
                'grade_level' => 4,
                'total_xp' => 100,
            ]);

        $target = $students->first();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rank = app(LeaderboardService::class)->rankForStudent($target, 'weekly');

        $queryCount = count(DB::getQueryLog());

        $this->assertGreaterThan(0, $rank);
        $this->assertLessThanOrEqual(3, $queryCount);

        Carbon::setTestNow();
    }

    public function test_digest_does_not_claim_weekly_slot_when_mail_queue_fails(): void
    {
        Carbon::setTestNow('2026-01-07 18:00:00');

        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP connection timed out'));

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedWeeklyActivity($parent, $student);

        (new DispatchWeeklyParentDigestJob)->handle(app(\App\Services\Analytics\ParentAnalyticsService::class));

        $this->assertDatabaseMissing('parent_report_logs', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'report_type' => 'weekly_digest',
        ]);

        Carbon::setTestNow();
    }

    public function test_only_weekly_digest_job_is_scheduled_for_saturday_evening(): void
    {
        $consoleRoutes = file_get_contents(base_path('routes/console.php'));

        $this->assertIsString($consoleRoutes);
        $this->assertStringContainsString('DispatchWeeklyParentDigestJob', $consoleRoutes);
        $this->assertStringContainsString("weeklyOn(6, '18:00')", $consoleRoutes);
        $this->assertStringNotContainsString('sanabel:send-weekly-summaries', $consoleRoutes);
    }

    private function seedWeeklyActivity(User $parent, Student $student): void
    {
        $material = \App\Models\ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Weekly activity',
                'status' => \App\Enums\MaterialStatus::Completed,
            ]);

        $activity = \App\Models\Activity::factory()
            ->for($student)
            ->for($material, 'parentMaterial')
            ->create([
                'status' => \App\Enums\ActivityStatus::Published,
                'xp_reward' => 25,
            ]);

        \App\Models\ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'xp_earned' => 25,
                'completed_at' => now()->subDays(2),
            ]);
    }
}
