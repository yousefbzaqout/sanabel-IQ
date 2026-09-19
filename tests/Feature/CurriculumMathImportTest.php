<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\Question;
use App\Models\Subject;
use App\Support\Curriculum\PalestinianCurriculumJsonImporter;
use App\Support\Curriculum\PalestinianCurriculumTransformer;
use App\Support\Curriculum\PalestinianLessonSchema;
use Database\Seeders\PalestinianCurriculumPrototypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurriculumMathImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_validates_math_number_packs_against_extended_schema(): void
    {
        $path = database_path('data/palestinian_curriculum/grade1/math/lessons/numbers-1-to-3.json');
        $this->assertFileExists($path);

        /** @var array<string, mixed> $pack */
        $pack = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        PalestinianLessonSchema::assertValid($pack);

        $this->assertSame('number', $pack['content_kind'] ?? null);
        $this->assertSame('MATH', $pack['subject_code']);
        $this->assertNotEmpty($pack['digit'] ?? $pack['digits'] ?? null);
        $this->assertNotEmpty($pack['stroke']['path']);
        $this->assertNotEmpty($pack['phonemes']['voice_targets']);

        $hasVisual = false;
        $hasComparison = false;
        foreach ($pack['quiz']['questions'] as $question) {
            if (isset($question['visual_items'])) {
                $hasVisual = true;
                $this->assertArrayHasKey('emoji', $question['visual_items']);
                $this->assertArrayHasKey('count', $question['visual_items']);
            }
            if (isset($question['comparison'])) {
                $hasComparison = true;
                $this->assertArrayHasKey('operator', $question['comparison']);
            }
            if (isset($question['mascot_hint'])) {
                $this->assertStringContainsString('سنبل', (string) $question['mascot_hint']);
            }
        }

        $this->assertTrue($hasVisual, 'Expected at least one visual counting question');
        $this->assertTrue($hasComparison, 'Expected at least one comparison question');
    }

    #[Test]
    public function it_transforms_math_pack_into_six_station_importer_definition(): void
    {
        $path = database_path('data/palestinian_curriculum/grade1/math/lessons/numbers-4-to-6.json');
        $this->assertFileExists($path);

        /** @var array<string, mixed> $pack */
        $pack = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $definition = app(PalestinianCurriculumTransformer::class)->toInteractiveDefinition($pack);

        $this->assertSame('MATH', $definition['subject_code']);
        $this->assertStringStartsWith('ar-g1-math-numbers-', $definition['lesson_key']);
        $this->assertSame('number_forms', $definition['structure']['mode']);
        $this->assertNotEmpty($definition['tracing']['path']);
        $this->assertCount(6, $definition['demo_station_labels']);
        $this->assertStringContainsString('سنبل', $definition['station_copy'][1]['sonbol_prompt']);
        $this->assertGreaterThanOrEqual(2, count($definition['quiz']['questions']));
    }

    #[Test]
    public function it_seeds_math_numbers_1_to_9_with_six_stations_each(): void
    {
        $this->seed(PalestinianCurriculumPrototypeSeeder::class);

        $subject = Subject::query()
            ->where('code', 'MATH')
            ->where('grade_level', 1)
            ->first();

        $this->assertNotNull($subject);
        $this->assertStringContainsString('الرياضيات', (string) $subject->name);

        $expectedKeys = [
            'ar-g1-math-numbers-1-3',
            'ar-g1-math-numbers-4-6',
            'ar-g1-math-numbers-7-9',
        ];

        foreach ($expectedKeys as $lessonKey) {
            $lesson = InteractiveLesson::query()->where('lesson_key', $lessonKey)->first();
            $this->assertNotNull($lesson, "Missing lesson {$lessonKey}");
            $this->assertSame('MATH', $lesson->subject_code);
            $this->assertNotNull($lesson->learning_material_id);

            $this->assertSame(
                6,
                InteractiveLessonStation::query()
                    ->where('interactive_lesson_id', $lesson->id)
                    ->count(),
            );

            $voice = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->where('station_type', 'variant_matrix')
                ->first();
            $this->assertNotNull($voice);
            $this->assertNotEmpty($voice->config['voice_targets'] ?? []);

            $stroke = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->where('station_type', 'trace_canvas')
                ->first();
            $this->assertNotNull($stroke);
            $this->assertNotEmpty($stroke->config['paths'][0]['d'] ?? null);

            $structure = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->where('station_type', 'structure_cards')
                ->first();
            $this->assertNotNull($structure);
            $this->assertSame('number_forms', $structure->config['mode'] ?? null);

            $questions = Question::query()
                ->where('learning_material_id', $lesson->learning_material_id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $questions);
        }
    }

    #[Test]
    public function curriculum_palestinian_validate_accepts_arabic_and_math_datasets(): void
    {
        $this->artisan('curriculum:palestinian', ['--validate' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('letter-raa')
            ->expectsOutputToContain('numbers-1-to-3')
            ->expectsOutputToContain('numbers-7-to-9');
    }

    #[Test]
    public function importer_returns_arabic_and_math_lesson_keys(): void
    {
        $keys = app(PalestinianCurriculumJsonImporter::class)->importPrototype();

        $this->assertContains('ar-g1-letter-raa', $keys);
        $this->assertContains('ar-g1-math-numbers-1-3', $keys);
        $this->assertContains('ar-g1-math-numbers-4-6', $keys);
        $this->assertContains('ar-g1-math-numbers-7-9', $keys);
    }
}
