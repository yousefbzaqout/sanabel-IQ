<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonDemo;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\Subject;
use App\Models\User;
use App\Support\Lessons\InteractiveLessonCatalog;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveLessonCatalogDbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_catalog_loads_ar_g1_letter_raa_from_interactive_lessons_table(): void
    {
        $this->seedLetterRaaInteractiveLesson();

        $payload = InteractiveLessonCatalog::get('ar-g1-letter-raa');

        $this->assertSame('database', $payload['source']);
        $this->assertSame('ar-g1-letter-raa', $payload['lesson_key']);
        $this->assertSame('حرف الراء', $payload['title']);
        $this->assertArrayHasKey('stations', $payload);
        $this->assertCount(6, $payload['stations']);

        $this->assertDatabaseHas('interactive_lessons', [
            'lesson_key' => 'ar-g1-letter-raa',
            'id' => $payload['interactive_lesson_id'],
        ]);

        $expectedTypes = [
            1 => 'variant_matrix',
            2 => 'sequence_pop',
            3 => 'structure_cards',
            4 => 'trace_canvas',
            5 => 'scratch_discover',
            6 => 'guided_demo',
        ];

        foreach ($expectedTypes as $number => $type) {
            $station = $payload['stations'][$number] ?? null;
            $this->assertIsArray($station);
            $this->assertSame($type, $station['type']);
            $this->assertIsArray($station['config']);
            $this->assertNotSame([], $station['config']);
        }

        $this->assertSame('رَمَل', $payload['stations'][2]['config']['target_word']);
        $this->assertArrayHasKey('tabs', $payload['stations'][1]['config']);
        $this->assertArrayHasKey('paths', $payload['stations'][4]['config']);
        $this->assertArrayHasKey('hotspots', $payload['stations'][5]['config']);
        $this->assertArrayHasKey('cta_label', $payload['stations'][6]['config']);
    }

    public function test_catalog_resolves_legacy_letter_raa_key_to_database_row(): void
    {
        $this->seedLetterRaaInteractiveLesson();

        $payload = InteractiveLessonCatalog::get(LetterRaaLessonDefinition::KEY);

        $this->assertSame('database', $payload['source']);
        $this->assertSame('ar-g1-letter-raa', $payload['lesson_key']);
        $this->assertCount(6, $payload['stations']);
    }

    public function test_demo_component_renders_six_stations_from_database_config(): void
    {
        [$parent, $student, $material] = $this->seedLetterRaaInteractiveLesson();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonDemo::class, [
            'lessonKey' => 'ar-g1-letter-raa',
        ])
            ->assertSet('lessonKey', 'ar-g1-letter-raa')
            ->assertSee('data-lesson-demo="ar-g1-letter-raa"', false)
            ->assertSee('data-station-type="variant_matrix"', false)
            ->assertSee('data-station-type="sequence_pop"', false)
            ->assertSee('data-station-type="structure_cards"', false)
            ->assertSee('data-station-type="trace_canvas"', false)
            ->assertSee('data-station-type="scratch_discover"', false)
            ->assertSee('data-station-type="guided_demo"', false)
            ->assertSee('data-diacritic="رَ"', false)
            ->assertSee('data-sound-bubble="رَ"', false)
            ->assertSee('data-sound-bubble="مَ"', false)
            ->assertSee('data-sound-bubble="لْ"', false)
            ->assertSee('data-position-card="begin"', false)
            ->assertSee('data-trace-canvas', false)
            ->assertSee('data-sand-scratch', false)
            ->assertSee('جاهز للاختبار يا بطل!', false)
            ->assertSee(
                'href="'.route('student.materials.quiz', $material).'"',
                false,
            );
    }

    public function test_catalog_falls_back_to_php_definition_when_db_row_missing(): void
    {
        $payload = InteractiveLessonCatalog::get(LetterRaaLessonDefinition::KEY);

        $this->assertSame('fallback', $payload['source']);
        $this->assertSame(LetterRaaLessonDefinition::KEY, $payload['key']);
        $this->assertArrayHasKey('diacritic_tabs', $payload);
        $this->assertSame(0, InteractiveLesson::query()->count());
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Student, 2: LearningMaterial}
     */
    private function seedLetterRaaInteractiveLesson(): array
    {
        $parent = User::factory()->create();
        $student = \App\Models\Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $subject = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);

        $material = LearningMaterial::factory()->for($subject)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();

        return [$parent, $student, $material];
    }
}
