<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\QuizRunner;
use App\Models\Badge;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentQuizCelebrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_quiz_celebration_page_renders_with_immersive_layout_and_confetti_trigger(): void
    {
        [$parent, $student, $material] = $this->seedCompletedQuizSession();

        $response = $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $material->id,
                    'xp_earned' => 80,
                    'percentage' => 100,
                    'streak_days' => 3,
                    'badge_ids' => [],
                ],
            ])
            ->get(route('student.quiz.completion', $material));

        $response->assertOk();
        $response->assertSee('data-quiz-celebration', false);
        $response->assertSee('data-confetti-blast', false);
        $response->assertSee('data-mascot-name="sonbol"', false);
        $response->assertDontSee(__('Dashboard'));
        $response->assertDontSee(__('Analytics'));
    }

    public function test_celebration_page_correctly_sums_and_animates_quiz_score_summary(): void
    {
        [$parent, $student, $material] = $this->seedCompletedQuizSession();

        $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $material->id,
                    'xp_earned' => 150,
                    'percentage' => 90,
                    'streak_days' => 7,
                    'badge_ids' => [],
                ],
            ])
            ->get(route('student.quiz.completion', $material))
            ->assertOk()
            ->assertSee('data-stat-xp="150"', false)
            ->assertSee('data-stat-accuracy="90"', false)
            ->assertSee('data-stat-streak="7"', false)
            ->assertSee('+150 XP', false)
            ->assertSee('دقة 90%', false)
            ->assertSee('سلسلة 7 أيام', false);
    }

    public function test_showcases_badges_unlocked_during_current_student_session(): void
    {
        [$parent, $student, $material] = $this->seedCompletedQuizSession();

        $badge = Badge::query()->updateOrCreate(['code' => 'celebration_star'], [
            'name_ar' => 'نجمة الاحتفال',
            'description_ar' => 'أكملت اختبارك بنجاح يا بطل!',
            'icon' => 'star',
            'criteria_type' => 'quiz_count',
            'criteria_value' => 99,
        ]);

        $student->badges()->attach($badge->id, ['unlocked_at' => now()]);

        $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $material->id,
                    'xp_earned' => 50,
                    'percentage' => 100,
                    'streak_days' => 1,
                    'badge_ids' => [$badge->id],
                ],
            ])
            ->get(route('student.quiz.completion', $material))
            ->assertOk()
            ->assertSee('data-badge-showcase', false)
            ->assertSee('نجمة الاحتفال')
            ->assertSee('أكملت اختبارك بنجاح يا بطل!')
            ->assertSee('data-badge-code="celebration_star"', false);
    }

    public function test_quiz_runner_redirects_to_celebration_after_final_submission(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $material = LearningMaterial::factory()->published()->create(['xp_reward' => 100]);

        $question = Question::factory()->for($material)->mcq()->create([
            'prompt' => 'سؤال واحد',
            'points' => 10,
            'order_column' => 0,
        ]);
        $correct = QuestionOption::factory()->for($question)->correct()->create([
            'option_text' => 'صح',
            'order_column' => 0,
        ]);
        QuestionOption::factory()->for($question)->create([
            'option_text' => 'خطأ',
            'is_correct' => false,
            'order_column' => 1,
        ]);

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(QuizRunner::class, [
            'learningMaterialId' => $material->id,
            'studentId' => $student->id,
        ])
            ->call('selectAnswer', $correct->id)
            ->call('advanceAfterFeedback')
            ->assertRedirect(route('student.quiz.completion', $material));

        $this->assertNotNull(session('quiz_celebration'));
        $this->assertSame($material->id, session('quiz_celebration.learning_material_id'));
        $this->assertGreaterThanOrEqual(0, (int) session('quiz_celebration.xp_earned'));
    }

    /**
     * @return array{0: User, 1: Student, 2: LearningMaterial}
     */
    private function seedCompletedQuizSession(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);
        $material = LearningMaterial::factory()->published()->create(['title' => 'Celebration Quiz']);

        return [$parent, $student, $material];
    }
}
