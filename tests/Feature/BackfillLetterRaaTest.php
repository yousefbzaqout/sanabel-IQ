<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\Grade1Semester1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillLetterRaaTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade1_seeder_backfills_letter_raa_interactive_lesson_with_six_stations(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $material = LearningMaterial::query()
            ->where('title', LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->firstOrFail();

        $lesson = InteractiveLesson::query()
            ->where('lesson_key', 'ar-g1-letter-raa')
            ->first();

        $this->assertNotNull($lesson);
        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->count());
        $this->assertTrue($lesson->learningMaterial->is($material));
        $this->assertSame('AR', $lesson->subject_code);
        $this->assertSame(1, $lesson->grade_level);
        $this->assertSame(6, $lesson->station_count);
        $this->assertSame('published', $lesson->status);
        $this->assertSame('حرف الراء', $lesson->title);

        $stations = InteractiveLessonStation::query()
            ->where('interactive_lesson_id', $lesson->id)
            ->orderBy('station_number')
            ->get();

        $this->assertCount(6, $stations);

        $expectedTypes = [
            1 => 'variant_matrix',
            2 => 'sequence_pop',
            3 => 'structure_cards',
            4 => 'trace_canvas',
            5 => 'scratch_discover',
            6 => 'guided_demo',
        ];

        foreach ($expectedTypes as $number => $type) {
            $station = $stations->firstWhere('station_number', $number);
            $this->assertNotNull($station, "Missing station {$number}");
            $this->assertSame($type, $station->station_type);
            $this->assertIsArray($station->config);
            $this->assertNotSame([], $station->config);
        }

        $variant = $stations->firstWhere('station_number', 1);
        $this->assertArrayHasKey('tabs', $variant->config);
        $this->assertCount(3, $variant->config['tabs']);
        $this->assertEquals(['رَ', 'رُ', 'رِ'], $variant->config['voice_targets']);

        $sequence = $stations->firstWhere('station_number', 2);
        $this->assertSame('رَمَل', $sequence->config['target_word']);
        $this->assertSame(
            ['رَ', 'مَ', 'لْ'],
            array_column($sequence->config['syllables'], 'glyph'),
        );

        $structure = $stations->firstWhere('station_number', 3);
        $this->assertSame('letter_position', $structure->config['mode']);
        $this->assertCount(3, $structure->config['cards']);

        $trace = $stations->firstWhere('station_number', 4);
        $this->assertSame('0 0 140 140', $trace->config['view_box']);
        $this->assertNotSame('', $trace->config['paths'][0]['d'] ?? '');

        $scratch = $stations->firstWhere('station_number', 5);
        $this->assertArrayHasKey('hotspots', $scratch->config);
        $this->assertGreaterThanOrEqual(3, count($scratch->config['hotspots']));

        $demo = $stations->firstWhere('station_number', 6);
        $this->assertSame($material->id, $demo->config['quiz_learning_material_id']);
        $this->assertArrayHasKey('parts', $demo->config);
        $this->assertArrayHasKey('cta_label', $demo->config);
    }

    public function test_letter_raa_backfill_is_idempotent(): void
    {
        $this->seed(Grade1Semester1Seeder::class);
        $this->seed(Grade1Semester1Seeder::class);

        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->count());
        $this->assertSame(
            6,
            InteractiveLessonStation::query()
                ->whereHas('interactiveLesson', fn ($q) => $q->where('lesson_key', 'ar-g1-letter-raa'))
                ->count(),
        );
    }
}
