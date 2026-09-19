<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use App\Support\Lessons\NumberThreeInteractiveLessonImporter;
use App\Support\Lessons\NumberThreeLessonDefinition;
use App\View\Components\Student\LearningMap;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicStudentInteractiveLessonRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_can_open_letter_raa_interactive_lesson_route(): void
    {
        [$parent, $student] = $this->seedInteractiveLessons();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/interactive-lesson/ar-g1-letter-raa')
            ->assertOk()
            ->assertSee('data-lesson-demo="ar-g1-letter-raa"', false)
            ->assertSee('data-lesson-station="1"', false)
            ->assertSee('data-station-type="variant_matrix"', false);
    }

    public function test_student_can_open_math_number_three_interactive_lesson_route(): void
    {
        [$parent, $student] = $this->seedInteractiveLessons();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/interactive-lesson/ar-g1-math-number-3')
            ->assertOk()
            ->assertSee('data-lesson-demo="ar-g1-math-number-3"', false)
            ->assertSee('data-lesson-station="1"', false)
            ->assertSee('data-station-type="variant_matrix"', false)
            ->assertSee('العدد 3', false);
    }

    public function test_invalid_interactive_lesson_key_returns_404(): void
    {
        [$parent, $student] = $this->seedInteractiveLessons();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/interactive-lesson/invalid-key')
            ->assertNotFound();
    }

    public function test_dashboard_quiz_path_links_materials_with_interactive_lessons(): void
    {
        [$parent, $student] = $this->seedInteractiveLessons();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee(
            'href="'.route('student.interactive-lesson.show', 'ar-g1-letter-raa').'"',
            false,
        );
        $response->assertSee(
            'href="'.route('student.interactive-lesson.show', 'ar-g1-math-number-3').'"',
            false,
        );
    }

    public function test_learning_map_prefers_interactive_lesson_url_when_available(): void
    {
        [$parent, $student] = $this->seedInteractiveLessons();

        $component = new LearningMap($student);
        $nodes = $component->nodes();

        $arabic = collect($nodes)->firstWhere('title', 'اللغة العربية');
        $this->assertNotNull($arabic);
        $this->assertSame('available', $arabic['state']);
        $this->assertSame(
            route('student.interactive-lesson.show', 'ar-g1-letter-raa'),
            $arabic['url'],
        );

        $letterMaterial = LearningMaterial::query()
            ->where('title', LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->firstOrFail();
        $mathMaterial = LearningMaterial::query()
            ->where('title', NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->firstOrFail();

        $this->assertSame(
            route('student.interactive-lesson.show', 'ar-g1-letter-raa'),
            $letterMaterial->studentLaunchUrl(),
        );
        $this->assertSame(
            route('student.interactive-lesson.show', 'ar-g1-math-number-3'),
            $mathMaterial->studentLaunchUrl(),
        );
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function seedInteractiveLessons(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
            'icon' => '🔤',
        ]);
        $math = Subject::factory()->create([
            'code' => 'MATH',
            'grade_level' => 1,
            'name' => 'الرياضيات',
            'icon' => '🔢',
        ]);

        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
            'order_column' => 1,
        ]);
        LearningMaterial::factory()->for($math)->create([
            'title' => NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
            'order_column' => 1,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();
        app(NumberThreeInteractiveLessonImporter::class)->import();

        return [$parent, $student];
    }
}
