<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentErgonomicsAndShellsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_interactive_elements_contain_48px_touch_target_classes(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);
        $material = LearningMaterial::factory()->published()->create(['title' => 'Ergonomics Quiz']);

        $question = Question::factory()->for($material)->mcq()->create([
            'prompt' => 'ما لون السماء؟',
            'order_column' => 0,
        ]);
        QuestionOption::factory()->for($question)->correct()->create([
            'option_text' => 'أزرق',
            'order_column' => 0,
        ]);
        QuestionOption::factory()->for($question)->create([
            'option_text' => 'أحمر',
            'is_correct' => false,
            'order_column' => 1,
        ]);

        $dashboard = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('data-audio-toggle', false);
        $dashboard->assertSee('min-h-12', false);
        $dashboard->assertSee('min-w-12', false);
        $dashboard->assertSee('data-mascot-name="sonbol"', false);

        $mascotHtml = (string) $this->blade(
            '<x-student.mascot state="happy" message="مرحباً يا بطل!" />',
        );
        $this->assertMatchesRegularExpression('/min-h-12/', $mascotHtml);
        $this->assertMatchesRegularExpression('/min-w-12/', $mascotHtml);

        $quiz = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $quiz->assertOk();
        $quiz->assertSee('min-h-12', false);
        $quiz->assertSee('min-w-12', false);
        $quiz->assertSee('aria-label="سماع السؤال"', false);
    }

    public function test_quiz_layouts_use_dynamic_viewport_height(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = LearningMaterial::factory()->published()->create();

        $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->create([
            'is_correct' => false,
            'order_column' => 1,
        ]);

        $quiz = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $quiz->assertOk();
        $quiz->assertSee('min-h-dvh', false);
        $quiz->assertDontSee('h-screen', false);
        $quiz->assertSee('overflow-y-auto', false);
        $quiz->assertSee('pb-[env(safe-area-inset-bottom', false);

        $celebration = $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $material->id,
                    'xp_earned' => 50,
                    'percentage' => 100,
                    'streak_days' => 1,
                    'badge_ids' => [],
                ],
            ])
            ->get(route('student.quiz.completion', $material));

        $celebration->assertOk();
        $celebration->assertSee('min-h-dvh', false);
        $celebration->assertDontSee('h-screen', false);
        $celebration->assertSee('overflow-y-auto', false);
        $celebration->assertSee('pb-[env(safe-area-inset-bottom', false);

        $quizLayout = file_get_contents(resource_path('views/layouts/quiz.blade.php'));
        $this->assertNotFalse($quizLayout);
        $this->assertStringContainsString('min-h-dvh', $quizLayout);
        $this->assertStringNotContainsString('h-screen', $quizLayout);
    }
}
