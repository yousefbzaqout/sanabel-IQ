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
use App\Models\User;
use App\Notifications\GoalAchievedNotification;
use App\Services\Gameplay\ActivitySubmissionService;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
