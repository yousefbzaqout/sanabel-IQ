<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildActivityGameplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_child_can_view_assigned_published_activities(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $otherStudent = Student::factory()->for($parent)->create();

        $publishedActivity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'title' => 'اختبار مساحة المثلث',
                'payload' => $this->samplePayload(),
            ]);

        Activity::factory()
            ->for($student)
            ->create(['status' => ActivityStatus::Draft, 'title' => 'Draft Hidden Activity']);

        Activity::factory()
            ->for($otherStudent)
            ->create(['status' => ActivityStatus::Published, 'title' => 'Other Child Activity']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.activities.index'))
            ->assertOk()
            ->assertViewIs('student.activities.index')
            ->assertSee('اختبار مساحة المثلث')
            ->assertDontSee('Draft Hidden Activity')
            ->assertDontSee('Other Child Activity')
            ->assertViewHas('activities', fn ($activities): bool => $activities->contains(
                fn (Activity $activity): bool => $activity->is($publishedActivity),
            ));
    }

    public function test_child_cannot_play_activity_belonging_to_another_student(): void
    {
        $parentA = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();

        $parentB = User::factory()->create();
        $studentB = Student::factory()->for($parentB)->create();

        $activity = Activity::factory()
            ->for($studentB)
            ->create([
                'status' => ActivityStatus::Published,
                'payload' => $this->samplePayload(),
            ]);

        $this->actingAs($parentA)
            ->withSession(['active_student_id' => $studentA->id])
            ->get(route('student.activities.play', $activity))
            ->assertForbidden();

        $this->actingAs($parentA)
            ->withSession(['active_student_id' => $studentA->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertForbidden();
    }

    public function test_submitting_activity_answers_calculates_score_and_awards_xp(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ]);

        $response->assertOk()
            ->assertJsonPath('score', 5)
            ->assertJsonPath('total_questions', 5)
            ->assertJsonPath('xp_earned', 50)
            ->assertJsonPath('percentage', 100);

        $this->assertDatabaseHas('activity_attempts', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'score' => 5,
            'total_questions' => 5,
            'xp_earned' => 50,
        ]);

        $this->assertSame(50, $student->fresh()->total_xp);
    }

    public function test_repeat_attempt_does_not_double_award_xp(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertOk();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertOk()
            ->assertJsonPath('xp_earned', 0);

        $this->assertSame(2, ActivityAttempt::query()->count());
        $this->assertSame(50, $student->fresh()->total_xp);
        $this->assertSame(0, ActivityAttempt::query()->orderByDesc('id')->first()->xp_earned);
    }

    public function test_submitting_empty_or_invalid_answers_returns_validation_error(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'payload' => $this->samplePayload(),
            ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'unexpected' => 'value',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => [0, 1],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers']);

        $this->assertDatabaseCount('activity_attempts', 0);
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
                    'question' => 'ما هي مساحة المربع الذي طول ضلعه 4 سم؟',
                    'options' => ['8 سم²', '16 سم²', '12 سم²', '4 سم²'],
                    'correct_index' => 1,
                    'explanation' => 'مساحة المربع = 4 × 4 = 16',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => 'كم ضلعاً للمثلث؟',
                    'options' => ['2', '3', '4', '5'],
                    'correct_index' => 1,
                    'explanation' => 'للمثلث ثلاثة أضلاع.',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '2 + 2 = ؟',
                    'options' => ['3', '4', '5', '6'],
                    'correct_index' => 1,
                    'explanation' => '2 + 2 = 4',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '5 × 5 = ؟',
                    'options' => ['20', '25', '30', '35'],
                    'correct_index' => 1,
                    'explanation' => '5 × 5 = 25',
                ],
                [
                    'type' => 'multiple_choice',
                    'question' => '10 ÷ 2 = ؟',
                    'options' => ['4', '5', '6', '7'],
                    'correct_index' => 1,
                    'explanation' => '10 ÷ 2 = 5',
                ],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function allCorrectAnswers(): array
    {
        return [1, 1, 1, 1, 1];
    }
}
