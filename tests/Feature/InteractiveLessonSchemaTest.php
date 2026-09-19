<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\MasteryConcept;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InteractiveLessonSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_interactive_lessons_and_stations_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('interactive_lessons'));
        $this->assertTrue(Schema::hasColumns('interactive_lessons', [
            'id',
            'learning_material_id',
            'lesson_key',
            'title',
            'subtitle',
            'subject_code',
            'grade_level',
            'station_count',
            'status',
            'intro_audio_path',
            'meta',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('interactive_lesson_stations'));
        $this->assertTrue(Schema::hasColumns('interactive_lesson_stations', [
            'id',
            'interactive_lesson_id',
            'station_number',
            'station_type',
            'title',
            'instructions',
            'sonbol_prompt',
            'config',
            'assets',
            'is_skippable',
            'order_column',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('mastery_concepts'));
        $this->assertTrue(Schema::hasColumns('mastery_concepts', [
            'id',
            'code',
            'label',
            'hint_template',
            'threshold',
            'meta',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('lesson_analytics', [
            'interactive_lesson_id',
            'learning_material_id',
        ]));
    }

    public function test_interactive_lesson_persists_with_jsonb_meta_and_material_relationship(): void
    {
        $material = LearningMaterial::factory()->create([
            'title' => 'الدرس الأول: حرف الراء',
        ]);

        $lesson = InteractiveLesson::query()->create([
            'learning_material_id' => $material->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'title' => 'حرف الراء',
            'subtitle' => 'رحلة تفاعلية',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'draft',
            'intro_audio_path' => 'audio/grade1/lessons/raa/intro.mp3',
            'meta' => [
                'estimated_minutes' => 12,
                'difficulty' => 'intro',
            ],
        ]);

        $this->assertDatabaseHas('interactive_lessons', [
            'id' => $lesson->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'learning_material_id' => $material->id,
            'subject_code' => 'AR',
        ]);

        $lesson->refresh();
        $this->assertEquals(['estimated_minutes' => 12, 'difficulty' => 'intro'], $lesson->meta);
        $this->assertTrue($lesson->learningMaterial->is($material));
        $this->assertTrue($material->fresh()->interactiveLesson->is($lesson));
    }

    public function test_station_persists_config_and_assets_as_arrays(): void
    {
        $lesson = $this->makeInteractiveLesson();

        $config = [
            'target_word' => 'رَمَل',
            'syllables' => [
                ['glyph' => 'رَ', 'order' => 1],
                ['glyph' => 'مَ', 'order' => 2],
                ['glyph' => 'لْ', 'order' => 3],
            ],
        ];
        $assets = [
            'completion_audio_path' => 'audio/grade1/lessons/raa/ramal.mp3',
        ];

        $station = InteractiveLessonStation::query()->create([
            'interactive_lesson_id' => $lesson->id,
            'station_number' => 2,
            'station_type' => 'sequence_pop',
            'title' => 'فرقعة الفقاعات',
            'instructions' => 'افقع المقاطع بالترتيب',
            'sonbol_prompt' => 'هيا نبني كلمة رَمَل!',
            'config' => $config,
            'assets' => $assets,
            'is_skippable' => false,
            'order_column' => 2,
        ]);

        $station->refresh();

        $this->assertSame('sequence_pop', $station->station_type);
        $this->assertEquals($config, $station->config);
        $this->assertEquals($assets, $station->assets);
        $this->assertTrue($station->interactiveLesson->is($lesson));
        $this->assertTrue($lesson->fresh()->stations->contains($station));
    }

    public function test_station_number_is_unique_per_interactive_lesson(): void
    {
        $lesson = $this->makeInteractiveLesson();

        InteractiveLessonStation::query()->create([
            'interactive_lesson_id' => $lesson->id,
            'station_number' => 1,
            'station_type' => 'variant_matrix',
            'title' => 'الحركات',
            'config' => [],
            'order_column' => 1,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        InteractiveLessonStation::query()->create([
            'interactive_lesson_id' => $lesson->id,
            'station_number' => 1,
            'station_type' => 'sequence_pop',
            'title' => 'duplicate',
            'config' => [],
            'order_column' => 2,
        ]);
    }

    public function test_subject_has_many_interactive_lessons_through_learning_materials(): void
    {
        $subject = Subject::factory()->create(['code' => 'AR', 'grade_level' => 1]);
        $material = LearningMaterial::factory()->for($subject)->create();
        $lesson = $this->makeInteractiveLesson($material, [
            'subject_code' => 'AR',
            'lesson_key' => 'ar-g1-letter-raa',
        ]);

        $this->assertTrue($subject->fresh()->interactiveLessons->contains($lesson));
    }

    public function test_mastery_concept_persists_with_meta_cast(): void
    {
        $concept = MasteryConcept::query()->create([
            'code' => 'diacritic_confusion',
            'label' => 'خلط الحركات',
            'hint_template' => 'ركز على صوت :glyph',
            'threshold' => 2,
            'meta' => ['station_hint' => 1],
        ]);

        $concept->refresh();

        $this->assertSame('diacritic_confusion', $concept->code);
        $this->assertSame(2, $concept->threshold);
        $this->assertEquals(['station_hint' => 1], $concept->meta);
    }

    public function test_lesson_analytics_can_link_to_interactive_lesson_and_material(): void
    {
        $student = Student::factory()->for(User::factory()->create())->create();
        $material = LearningMaterial::factory()->create();
        $lesson = $this->makeInteractiveLesson($material, [
            'lesson_key' => 'ar-g1-letter-raa',
        ]);

        $analytic = LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'interactive_lesson_id' => $lesson->id,
            'learning_material_id' => $material->id,
            'station' => 1,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'error',
            'error_count' => 1,
            'payload' => ['expected' => 'رَ'],
        ]);

        $analytic->refresh();

        $this->assertTrue($analytic->interactiveLesson->is($lesson));
        $this->assertTrue($analytic->learningMaterial->is($material));
        $this->assertTrue($lesson->fresh()->analytics->contains($analytic));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeInteractiveLesson(?LearningMaterial $material = null, array $overrides = []): InteractiveLesson
    {
        $material ??= LearningMaterial::factory()->create();

        return InteractiveLesson::query()->create(array_merge([
            'learning_material_id' => $material->id,
            'lesson_key' => 'lesson-'.uniqid(),
            'title' => 'Test interactive lesson',
            'subtitle' => null,
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'draft',
            'intro_audio_path' => null,
            'meta' => null,
        ], $overrides));
    }
}
