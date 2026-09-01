<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ParentGoalStatus;
use App\Enums\QuestionType;
use App\Events\ActivityCompletedBroadcastEvent;
use App\Models\LearningMaterial;
use App\Models\ParentLearningGoal;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\StudentQuizAnswer;
use App\Models\StudentQuizAttempt;
use App\Models\User;
use App\Services\Goals\ParentGoalEvaluatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class StudentQuizExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_payload_does_not_leak_is_correct_answers_to_student(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 50]);

        $question = Question::factory()->for($material)->mcq()->create([
            'prompt' => 'ما حاصل 2 + 3؟',
            'points' => 10,
            'order_column' => 0,
        ]);

        QuestionOption::factory()->for($question)->create([
            'option_text' => '4',
            'is_correct' => false,
            'order_column' => 0,
        ]);
        QuestionOption::factory()->for($question)->correct()->create([
            'option_text' => '5',
            'order_column' => 1,
        ]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('student.materials.quiz', $material))
            ->assertOk()
            ->assertJsonStructure([
                'material' => ['id', 'title', 'xp_reward'],
                'questions' => [
                    '*' => [
                        'id',
                        'type',
                        'prompt',
                        'points',
                        'order_column',
                        'options' => [
                            '*' => ['id', 'option_text', 'order_column'],
                        ],
                    ],
                ],
            ]);

        $this->assertQuizPayloadDoesNotLeakCorrectAnswers($response);
    }

    public function test_student_can_submit_quiz_and_receive_instant_score_and_xp(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 90]);

        $questions = $this->createThreeQuestionQuiz($material);

        $answers = [
            ['question_id' => $questions[0]['question']->id, 'selected_option_id' => $questions[0]['correct']->id],
            ['question_id' => $questions[1]['question']->id, 'selected_option_id' => $questions[1]['correct']->id],
            ['question_id' => $questions[2]['question']->id, 'selected_option_id' => $questions[2]['incorrect']->id],
        ];

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => $answers,
            ])
            ->assertOk()
            ->assertJson([
                'score' => 2,
                'total_questions' => 3,
                'percentage' => 67,
                'score_percentage' => 66.67,
                'xp_earned' => 60,
                'total_xp' => 60,
            ]);

        $this->assertDatabaseHas('student_quiz_attempts', [
            'student_id' => $student->id,
            'learning_material_id' => $material->id,
            'total_questions' => 3,
            'correct_answers' => 2,
            'score_percentage' => 66.67,
            'xp_earned' => 60,
        ]);

        $attempt = StudentQuizAttempt::query()->firstOrFail();
        $this->assertDatabaseCount('student_quiz_answers', 3);
        $this->assertDatabaseHas('student_quiz_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $questions[0]['question']->id,
            'is_correct' => true,
            'points_awarded' => 10,
        ]);
        $this->assertDatabaseHas('student_quiz_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $questions[2]['question']->id,
            'is_correct' => false,
            'points_awarded' => 0,
        ]);
    }

    public function test_quiz_completion_dispatches_realtime_broadcast_and_evaluates_parent_goals(): void
    {
        Event::fake([ActivityCompletedBroadcastEvent::class]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'total_xp' => 0,
        ]);
        $material = LearningMaterial::factory()->published()->create([
            'title' => 'اختبار الوحدة الأولى',
            'xp_reward' => 90,
        ]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 60,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $questions = $this->createThreeQuestionQuiz($material);

        $this->mock(ParentGoalEvaluatorService::class)
            ->shouldReceive('evaluate')
            ->once()
            ->with(Mockery::on(fn (Student $evaluatedStudent): bool => $evaluatedStudent->id === $student->id));

        $answers = [
            ['question_id' => $questions[0]['question']->id, 'selected_option_id' => $questions[0]['correct']->id],
            ['question_id' => $questions[1]['question']->id, 'selected_option_id' => $questions[1]['correct']->id],
            ['question_id' => $questions[2]['question']->id, 'selected_option_id' => $questions[2]['incorrect']->id],
        ];

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => $answers,
            ])
            ->assertOk();

        Event::assertDispatched(ActivityCompletedBroadcastEvent::class, function (ActivityCompletedBroadcastEvent $event) use ($parent, $student, $material): bool {
            $channels = $event->broadcastOn();

            $this->assertCount(1, $channels);
            $this->assertSame('private-parent.'.$parent->id, $channels[0]->name);

            $payload = $event->broadcastWith();

            $this->assertSame($student->name, $payload['child_name']);
            $this->assertSame($material->title, $payload['activity_title']);
            $this->assertSame(67, $payload['score_percent']);
            $this->assertSame(60, $payload['xp_earned']);

            return true;
        });
    }

    public function test_student_cannot_take_quiz_for_unpublished_material(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = LearningMaterial::factory()->create(['is_published' => false]);

        $question = Question::factory()->for($material)->mcq()->create();
        QuestionOption::factory()->for($question)->correct()->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('student.materials.quiz', $material))
            ->assertNotFound();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.materials.quiz.submit', $material), [
                'answers' => [
                    ['question_id' => $question->id, 'selected_option_id' => $question->options()->firstOrFail()->id],
                ],
            ])
            ->assertNotFound();
    }

    /**
     * @return list<array{question: Question, correct: QuestionOption, incorrect: QuestionOption}>
     */
    private function createThreeQuestionQuiz(LearningMaterial $material): array
    {
        $quiz = [];

        for ($index = 0; $index < 3; $index++) {
            $question = Question::factory()->for($material)->mcq()->create([
                'prompt' => 'سؤال '.($index + 1),
                'points' => 10,
                'order_column' => $index,
            ]);

            $incorrect = QuestionOption::factory()->for($question)->create([
                'option_text' => 'خطأ '.$index,
                'is_correct' => false,
                'order_column' => 0,
            ]);
            $correct = QuestionOption::factory()->for($question)->correct()->create([
                'option_text' => 'صح '.$index,
                'order_column' => 1,
            ]);

            $quiz[] = [
                'question' => $question,
                'correct' => $correct,
                'incorrect' => $incorrect,
            ];
        }

        return $quiz;
    }

    private function assertQuizPayloadDoesNotLeakCorrectAnswers(TestResponse $response): void
    {
        $payload = $response->json();

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey('is_correct', $payload);

        foreach ($payload['questions'] ?? [] as $question) {
            $this->assertArrayNotHasKey('is_correct', $question);

            foreach ($question['options'] ?? [] as $option) {
                $this->assertArrayNotHasKey('is_correct', $option);
            }
        }

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('"is_correct"', $encoded);
    }
}
