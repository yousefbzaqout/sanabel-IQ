<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Enums\ParentGoalStatus;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\ParentLearningGoal;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\GoalAchievedNotification;
use App\Services\Export\StudentReportExportService;
use App\Services\Gameplay\ActivitySubmissionService;
use App\Services\Goals\ParentGoalEvaluatorService;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ParentGoalAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        $this->seed(SubjectSeeder::class);
    }

    public function test_parent_can_view_comparative_analytics_for_all_children(): void
    {
        $parent = User::factory()->create();
        $firstChild = Student::factory()->for($parent)->create([
            'name' => 'Child Alpha',
            'total_xp' => 120,
        ]);
        $secondChild = Student::factory()->for($parent)->create([
            'name' => 'Child Beta',
            'total_xp' => 80,
        ]);

        $this->seedAttemptForStudent($parent, $firstChild, accuracyPercent: 80, xpEarned: 50);
        $this->seedAttemptForStudent($parent, $secondChild, accuracyPercent: 40, xpEarned: 30);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $firstChild->id])
            ->get(route('parent.comparative-analytics'))
            ->assertOk()
            ->assertSee('Child Alpha')
            ->assertSee('Child Beta')
            ->assertSee('80%')
            ->assertSee('40%')
            ->assertSee('120')
            ->assertSee('80');
    }

    public function test_parent_can_export_child_progress_report_as_pdf_and_csv(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Export Student',
            'total_xp' => 150,
        ]);

        $this->seedAttemptForStudent($parent, $student, accuracyPercent: 100, xpEarned: 50);

        $csvResponse = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'csv',
            ]));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Export Student', $csvResponse->streamedContent());
        $this->assertStringContainsString('150', $csvResponse->streamedContent());
        $this->assertStringContainsString('100', $csvResponse->streamedContent());

        $pdfResponse = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'pdf',
            ]));

        $pdfResponse->assertOk();
        $pdfResponse->assertSee('Export Student');
        $pdfResponse->assertSee('150');
    }

    public function test_parent_can_create_custom_weekly_goal_for_child(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->post(route('parent.goals.store'), [
                'student_id' => $student->id,
                'subject_id' => null,
                'target_activity_count' => 5,
                'target_xp' => 200,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('parent_learning_goals', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'subject_id' => null,
            'target_activity_count' => 5,
            'target_xp' => 200,
            'status' => ParentGoalStatus::Pending->value,
        ]);
    }

    public function test_goal_status_automatically_updates_to_achieved_when_child_completes_targets(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        $goal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 2,
            'target_xp' => 100,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activityOne = Activity::factory()->for($student)->create([
            'status' => ActivityStatus::Published,
            'xp_reward' => 100,
            'payload' => $this->samplePayload(),
        ]);

        $activityTwo = Activity::factory()->for($student)->create([
            'status' => ActivityStatus::Published,
            'xp_reward' => 100,
            'payload' => $this->samplePayload(),
        ]);

        app(ActivitySubmissionService::class)->submit(
            $activityOne,
            $student,
            $this->allCorrectAnswers(),
        );

        $goal->refresh();
        $this->assertSame(ParentGoalStatus::Pending, $goal->status);

        app(ActivitySubmissionService::class)->submit(
            $activityTwo,
            $student,
            $this->allCorrectAnswers(),
        );

        $goal->refresh();
        $this->assertSame(ParentGoalStatus::Achieved, $goal->status);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $parent->id,
            'type' => GoalAchievedNotification::class,
        ]);
    }

    public function test_expired_pending_goal_is_marked_expired_without_achievement_notification(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $goal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 10,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        app(ParentGoalEvaluatorService::class)->evaluate($student);

        $goal->refresh();
        $this->assertSame(ParentGoalStatus::Expired, $goal->status);
        Notification::assertNothingSent();
    }

    public function test_subject_specific_goals_only_count_matching_material_attempts(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $mathSubject = Subject::query()->where('slug', 'math')->firstOrFail();
        $scienceSubject = Subject::query()->where('slug', 'science')->firstOrFail();
        $evaluator = app(ParentGoalEvaluatorService::class);

        $generalGoal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'subject_id' => null,
            'target_activity_count' => 5,
            'target_xp' => 0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
        ]);

        $mathGoal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'subject_id' => $mathSubject->id,
            'target_activity_count' => 3,
            'target_xp' => 0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
        ]);

        $scienceMaterial = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Science Worksheet',
                'subject_id' => $scienceSubject->id,
                'status' => MaterialStatus::Completed,
            ]);

        $scienceActivity = Activity::factory()->for($student)->create([
            'parent_material_id' => $scienceMaterial->id,
            'status' => ActivityStatus::Published,
        ]);

        ActivityAttempt::factory()->for($student)->for($scienceActivity)->create([
            'score' => 5,
            'total_questions' => 5,
            'xp_earned' => 20,
            'completed_at' => now(),
        ]);

        $generalProgress = $evaluator->progressForGoal($student, $generalGoal);
        $mathProgress = $evaluator->progressForGoal($student, $mathGoal);

        $this->assertSame(1, $generalProgress['activities_completed']);
        $this->assertSame(0, $mathProgress['activities_completed']);
    }

    public function test_csv_export_sanitizes_formula_injection_and_includes_utf8_bom(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => '=SUM(1+1)',
        ]);

        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'الرياضيات & العلوم",',
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()->for($student)->create([
            'parent_material_id' => $material->id,
            'status' => ActivityStatus::Published,
        ]);

        ActivityAttempt::factory()->for($student)->for($activity)->create([
            'score' => 5,
            'total_questions' => 5,
            'xp_earned' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'csv',
            ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringContainsString('الرياضيات & العلوم', $csv);
    }

    public function test_export_handles_student_with_zero_attempt_history(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Fresh Learner',
            'total_xp' => 0,
        ]);

        $csvResponse = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'csv',
            ]));

        $csvResponse->assertOk();
        $this->assertStringContainsString('Fresh Learner', $csvResponse->streamedContent());
        $this->assertStringContainsString('0', $csvResponse->streamedContent());

        $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'pdf',
            ]))
            ->assertOk()
            ->assertSee('Fresh Learner')
            ->assertSee(__('No badges earned yet.'));
    }

    public function test_goal_achievement_notification_is_dispatched_exactly_once_under_repeated_evaluation(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        $goal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activity = Activity::factory()->for($student)->create([
            'status' => ActivityStatus::Published,
            'xp_reward' => 50,
            'payload' => $this->samplePayload(),
        ]);

        app(ActivitySubmissionService::class)->submit(
            $activity,
            $student,
            $this->allCorrectAnswers(),
        );

        $evaluator = app(ParentGoalEvaluatorService::class);
        $evaluator->evaluateGoal($student, $goal);
        $evaluator->evaluateGoal($student, $goal->fresh());

        $goal->refresh();
        $this->assertSame(ParentGoalStatus::Achieved, $goal->status);
        $this->assertSame(
            1,
            $parent->fresh()?->notifications()->where('type', GoalAchievedNotification::class)->count(),
        );
    }

    public function test_parent_cannot_delete_another_parents_learning_goal(): void
    {
        $parentA = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        $goal = ParentLearningGoal::factory()->for($parentA, 'parent')->for($studentA)->create();

        $parentB = User::factory()->create();
        Student::factory()->for($parentB)->create();

        $this->actingAs($parentB)
            ->withSession(['active_student_id' => Student::query()->where('user_id', $parentB->id)->value('id')])
            ->delete(route('parent.goals.destroy', $goal))
            ->assertForbidden();

        $this->assertModelExists($goal);
    }

    public function test_csv_cell_sanitizer_prefixes_dangerous_leading_characters(): void
    {
        $service = app(StudentReportExportService::class);

        $this->assertSame("'=SUM(1+1)", $service->sanitizeCsvCell('=SUM(1+1)'));
        $this->assertSame("'+123", $service->sanitizeCsvCell('+123'));
        $this->assertSame("'@cmd", $service->sanitizeCsvCell('@cmd'));
        $this->assertSame('Safe Name', $service->sanitizeCsvCell('Safe Name'));
    }

    /**
     * @return array{questions: list<array<string, mixed>>}
     */
    private function samplePayload(): array
    {
        return [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'question' => '2 + 2 = ?',
                    'options' => ['3', '4', '5'],
                    'correct_index' => 1,
                    'explanation' => 'Answer is 4.',
                ],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function allCorrectAnswers(): array
    {
        return [1];
    }

    private function seedAttemptForStudent(
        User $parent,
        Student $student,
        int $accuracyPercent,
        int $xpEarned,
    ): void {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Math',
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()->for($student)->create([
            'parent_material_id' => $material->id,
            'status' => ActivityStatus::Published,
            'xp_reward' => $xpEarned,
        ]);

        $totalQuestions = 5;
        $score = (int) round($totalQuestions * ($accuracyPercent / 100));

        ActivityAttempt::factory()->for($student)->for($activity)->create([
            'score' => $score,
            'total_questions' => $totalQuestions,
            'xp_earned' => $xpEarned,
            'completed_at' => now()->subDay(),
        ]);
    }
}
