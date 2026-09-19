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

class StudentMotionAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_views_include_reduced_motion_safeguards(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);

        $mascot = (string) $this->blade(
            '<x-student.mascot state="happy" message="مرحباً!" />',
        );
        $this->assertTrue(
            str_contains($mascot, 'motion-safe:')
            || str_contains($mascot, 'prefers-reduced-motion')
            || str_contains($mascot, 'data-motion-safe'),
            'Mascot should gate infinite motion behind reduced-motion safeguards.',
        );

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 1]);

        $map = (string) $this->blade(
            '<x-student.learning-map :student="$student" />',
            ['student' => $student],
        );
        $this->assertTrue(
            str_contains($map, 'motion-safe:')
            || str_contains($map, 'prefers-reduced-motion')
            || ! str_contains($map, 'animate-ping'),
            'Learning map should not use ungated infinite ping.',
        );

        $badgeShowcase = file_get_contents(resource_path('views/components/student/badge-showcase.blade.php'));
        $this->assertNotFalse($badgeShowcase);
        $this->assertTrue(
            str_contains($badgeShowcase, 'motion-safe:')
            || str_contains($badgeShowcase, 'prefers-reduced-motion'),
            'Badge showcase should gate shimmer/spin behind reduced-motion safeguards.',
        );
    }

    public function test_confetti_triggers_use_bounded_particle_counts(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($js);

        $this->assertStringContainsString("prefers-reduced-motion: reduce", $js);
        $this->assertStringContainsString('matchMedia', $js);

        preg_match_all('/burstConfetti\(\s*(\d+)\s*\)/', $js, $matches);
        $this->assertNotEmpty($matches[1], 'Expected explicit burstConfetti particle budgets.');

        foreach ($matches[1] as $count) {
            $this->assertLessThanOrEqual(100, (int) $count, "Particle budget {$count} exceeds 100.");
        }

        preg_match_all('/burstConfetti\(count\s*=\s*(\d+)\)/', $js, $defaults);
        foreach ($defaults[1] as $count) {
            $this->assertLessThanOrEqual(100, (int) $count);
        }

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $material = LearningMaterial::factory()->published()->create();
        $question = Question::factory()->for($material)->mcq()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->correct()->create(['order_column' => 0]);
        QuestionOption::factory()->for($question)->create(['is_correct' => false, 'order_column' => 1]);

        $this->actingAs($parent)
            ->withSession([
                'active_student_id' => $student->id,
                'quiz_celebration' => [
                    'learning_material_id' => $material->id,
                    'xp_earned' => 40,
                    'percentage' => 100,
                    'streak_days' => 1,
                    'badge_ids' => [],
                ],
            ])
            ->get(route('student.quiz.completion', $material))
            ->assertOk()
            ->assertSee('data-confetti-blast', false)
            ->assertSee('data-reduced-motion-fallback', false);
    }
}
