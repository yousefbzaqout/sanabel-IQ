<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\View\Components\Student\LearningMap;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_learning_map_component_renders_subjects_as_distinct_nodes(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 3]);

        foreach (['Math', 'Science', 'Arabic'] as $index => $name) {
            $subject = Subject::factory()->create([
                'name' => $name,
                'grade_level' => 3,
                'code' => 'SUB-'.$index.'-G3',
            ]);

            LearningMaterial::factory()->published()->create([
                'subject_id' => $subject->id,
                'title' => $name.' Quiz',
                'order_column' => $index + 1,
            ]);
        }

        $html = (string) $this->blade(
            '<x-student.learning-map :student="$student" />',
            ['student' => $student],
        );

        $this->assertStringContainsString('data-learning-map', $html);
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'data-map-node'));
        $this->assertStringContainsString('Math', $html);
        $this->assertStringContainsString('Science', $html);
        $this->assertStringContainsString('Arabic', $html);
    }

    public function test_subject_node_states_reflect_student_completion_status(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 3]);

        $subjectA = Subject::factory()->create([
            'name' => 'Subject A',
            'grade_level' => 3,
            'code' => 'A-G3',
        ]);
        $subjectB = Subject::factory()->create([
            'name' => 'Subject B',
            'grade_level' => 3,
            'code' => 'B-G3',
        ]);

        $materialA = LearningMaterial::factory()->published()->create([
            'subject_id' => $subjectA->id,
            'title' => 'Quiz A',
            'order_column' => 1,
        ]);
        LearningMaterial::factory()->published()->create([
            'subject_id' => $subjectB->id,
            'title' => 'Quiz B',
            'order_column' => 1,
        ]);

        StudentQuizAttempt::factory()->for($student)->create([
            'learning_material_id' => $materialA->id,
            'xp_earned' => 40,
        ]);

        $html = (string) $this->blade(
            '<x-student.learning-map :student="$student" />',
            ['student' => $student],
        );

        $this->assertMatchesRegularExpression(
            '/data-map-node[^>]*data-node-state="completed"[^>]*>[\s\S]*?Subject A/u',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-map-node[^>]*data-node-state="available"[^>]*>[\s\S]*?Subject B/u',
            $html,
        );
        $this->assertStringContainsString('data-mascot-name="sonbol"', $html);
    }

    public function test_learning_map_links_correctly_to_quiz_or_subject_routes(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 2]);

        $subject = Subject::factory()->create([
            'name' => 'Arabic',
            'grade_level' => 2,
            'code' => 'AR-G2',
        ]);
        $material = LearningMaterial::factory()->published()->create([
            'subject_id' => $subject->id,
            'title' => 'Letters Quiz',
            'order_column' => 1,
        ]);

        $component = new LearningMap($student);
        $nodes = $component->nodes();

        $this->assertNotEmpty($nodes);
        $available = collect($nodes)->firstWhere('state', 'available');

        $this->assertNotNull($available);
        $this->assertSame('available', $available['state']);
        $this->assertSame(
            route('student.materials.quiz', $material),
            $available['url'],
        );

        $html = (string) $this->blade(
            '<x-student.learning-map :student="$student" />',
            ['student' => $student],
        );

        $this->assertStringContainsString(
            'href="'.route('student.materials.quiz', $material).'"',
            $html,
        );
        $this->assertStringContainsString('data-node-state="available"', $html);
    }

    public function test_student_dashboard_includes_learning_map(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 4, 'name' => 'ليان']);

        $subject = Subject::factory()->create([
            'name' => 'Science',
            'grade_level' => 4,
            'code' => 'SCI-G4',
        ]);
        LearningMaterial::factory()->published()->create([
            'subject_id' => $subject->id,
            'title' => 'Plants',
        ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-learning-map', false)
            ->assertSee('Science')
            ->assertSee('ليان');
    }
}
