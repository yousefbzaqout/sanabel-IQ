<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Enums\ParentGoalStatus;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\LearningMaterial;
use App\Models\ParentLearningGoal;
use App\Models\ParentMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\StudentStreak;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ParentReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_parent_can_view_analytics_dashboard_for_their_child(): void
    {
        Carbon::setTestNow('2026-01-07 12:00:00');

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 3,
            'total_xp' => 120,
        ]);

        StudentStreak::factory()->for($student)->create([
            'current_streak' => 4,
            'max_streak' => 6,
            'last_activity_date' => now()->toDateString(),
        ]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'status' => ParentGoalStatus::Achieved,
            'target_activity_count' => 1,
            'target_xp' => 50,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->seedWeeklyActivityAttempt($parent, $student, xpEarned: 30, accuracyPercent: 80);
        $this->seedWeeklyQuizAttempt($student, correctAnswers: 2, totalQuestions: 2, xpEarned: 20);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.analytics.show', $student));

        $response->assertOk()
            ->assertViewIs('parent.analytics.show')
            ->assertViewHas('summary', fn ($summary): bool => $summary->xpEarnedInPeriod === 50
                && $summary->quizAccuracyPercent === 100
                && $summary->completedGoalsCount === 1
                && $summary->currentStreak === 4);

        Carbon::setTestNow();
    }

    public function test_parent_cannot_view_analytics_or_export_reports_for_other_parents_child(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();

        $this->actingAs($parentB)
            ->get(route('parent.analytics.show', $studentA))
            ->assertForbidden();

        $this->actingAs($parentB)
            ->get(route('parent.analytics.export.pdf', [
                'student' => $studentA,
                'period' => 'weekly',
            ]))
            ->assertForbidden();

        $this->actingAs($parentB)
            ->get(route('parent.analytics.export.excel', [
                'student' => $studentA,
                'period' => 'weekly',
            ]))
            ->assertForbidden();
    }

    public function test_parent_can_download_weekly_pdf_report_with_arabic_formatting(): void
    {
        Carbon::setTestNow('2026-01-07 12:00:00');

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'أحمد',
            'grade_level' => 2,
        ]);

        $this->seedWeeklyQuizAttempt($student, correctAnswers: 1, totalQuestions: 1, xpEarned: 25);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.analytics.export.pdf', [
                'student' => $student,
                'period' => 'weekly',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', strtolower((string) $response->headers->get('Content-Disposition')));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertDatabaseHas('parent_report_logs', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'report_type' => 'weekly',
        ]);

        Carbon::setTestNow();
    }

    public function test_parent_can_download_excel_summary_report(): void
    {
        Carbon::setTestNow('2026-01-07 12:00:00');

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'سارة',
            'grade_level' => 4,
        ]);

        $this->seedWeeklyActivityAttempt($parent, $student, xpEarned: 40, accuracyPercent: 100);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.analytics.export.excel', [
                'student' => $student,
                'period' => 'weekly',
            ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $this->assertStringContainsString('attachment', strtolower((string) $response->headers->get('Content-Disposition')));

        $this->assertDatabaseHas('parent_report_logs', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'report_type' => 'weekly',
        ]);

        Carbon::setTestNow();
    }

    private function seedWeeklyActivityAttempt(
        User $parent,
        Student $student,
        int $xpEarned,
        int $accuracyPercent,
    ): void {
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

        $totalQuestions = 5;
        $score = (int) round(($accuracyPercent / 100) * $totalQuestions);

        ActivityAttempt::factory()
            ->for($student)
            ->for($activity)
            ->create([
                'score' => $score,
                'total_questions' => $totalQuestions,
                'xp_earned' => $xpEarned,
                'completed_at' => now()->subDay(),
            ]);
    }

    private function seedWeeklyQuizAttempt(
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
            'score_percentage' => $totalQuestions > 0
                ? round(($correctAnswers / $totalQuestions) * 100, 2)
                : 0,
            'xp_earned' => $xpEarned,
            'completed_at' => now()->subHours(6),
        ]);
    }
}
