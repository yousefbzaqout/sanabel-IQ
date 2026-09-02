<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\QuizRunner;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentImmersiveQuizRunnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_quiz_runner_renders_with_immersive_layout_and_no_nav_header(): void
    {
        [$parent, $student, $material] = $this->seedQuiz(questionCount: 2);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $response->assertOk();
        $response->assertSee('data-quiz-immersive', false);
        $response->assertSee('data-quiz-progress', false);
        $response->assertSee('السؤال', false);
        $response->assertDontSee(__('Dashboard'));
        $response->assertDontSee(__('Analytics'));
        $response->assertDontSee(__('Compare'));
        $response->assertDontSee(__('Children'));
        $response->assertSee('data-mascot-name="sonbol"', false);
    }

    public function test_mascot_state_changes_reflect_correct_versus_incorrect_answers(): void
    {
        [$parent, $student, $material, $questions] = $this->seedQuiz(questionCount: 2);

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(QuizRunner::class, [
            'learningMaterialId' => $material->id,
            'studentId' => $student->id,
        ])
            ->assertSet('mascotState', 'thinking')
            ->call('selectAnswer', $questions[0]['correct']->id)
            ->assertSet('mascotState', 'happy')
            ->assertSet('lastAnswerCorrect', true)
            ->call('advanceAfterFeedback')
            ->assertSet('mascotState', 'thinking')
            ->call('selectAnswer', $questions[1]['incorrect']->id)
            ->assertSet('mascotState', 'encouraging')
            ->assertSet('lastAnswerCorrect', false);
    }

    public function test_progress_bar_increments_correctly_based_on_score(): void
    {
        [$parent, $student, $material, $questions] = $this->seedQuiz(questionCount: 4);

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        $component = Livewire::test(QuizRunner::class, [
            'learningMaterialId' => $material->id,
            'studentId' => $student->id,
        ])
            ->assertSet('progressPercent', 0)
            ->assertSeeHtml('data-quiz-progress="0"');

        $component
            ->call('selectAnswer', $questions[0]['correct']->id)
            ->assertSet('correctCount', 1)
            ->assertSet('progressPercent', 25)
            ->assertSeeHtml('data-quiz-progress="25"')
            ->call('advanceAfterFeedback')
            ->call('selectAnswer', $questions[1]['correct']->id)
            ->assertSet('correctCount', 2)
            ->assertSet('progressPercent', 50)
            ->assertSeeHtml('data-quiz-progress="50"');
    }

    /**
     * @return array{0: User, 1: Student, 2: LearningMaterial, 3: list<array{question: Question, correct: QuestionOption, incorrect: QuestionOption}>}
     */
    private function seedQuiz(int $questionCount = 2): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'total_xp' => 0,
        ]);
        $material = LearningMaterial::factory()->published()->create([
            'title' => 'Immersive Quiz',
            'xp_reward' => 100,
        ]);

        $questions = [];

        for ($index = 0; $index < $questionCount; $index++) {
            $question = Question::factory()->for($material)->mcq()->create([
                'prompt' => 'سؤال رقم '.($index + 1),
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

            $questions[] = [
                'question' => $question,
                'correct' => $correct,
                'incorrect' => $incorrect,
            ];
        }

        return [$parent, $student, $material, $questions];
    }
}
