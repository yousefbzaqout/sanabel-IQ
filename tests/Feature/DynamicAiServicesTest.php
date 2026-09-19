<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\MasteryConcept;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AdaptiveMasteryEngine;
use App\Services\Ai\ArabicPronunciationScorer;
use App\Services\Ai\LetterStrokeDirectionAnalyzer;
use App\Services\Lessons\InteractiveStationConfigRepository;
use App\Services\MascotAiFeedbackService;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use App\Support\Lessons\NumberThreeInteractiveLessonImporter;
use App\Support\Lessons\NumberThreeLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\MasteryConceptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicAiServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        $this->seed(MasteryConceptSeeder::class);
        $this->seedInteractiveLessons();
    }

    public function test_voice_evaluator_extracts_targets_for_letter_raa_and_math_number_three(): void
    {
        $repo = app(InteractiveStationConfigRepository::class);
        $scorer = app(ArabicPronunciationScorer::class);

        $raaTargets = $repo->voiceTargets('ar-g1-letter-raa');
        $this->assertEqualsCanonicalizing(['رَ', 'رُ', 'رِ'], $raaTargets);
        $this->assertTrue($scorer->isAllowedTarget('رَ', $raaTargets));
        $this->assertSame('match', $scorer->score('رَ', 'را', $raaTargets)['result']);

        $mathTargets = $repo->voiceTargets('ar-g1-math-number-3');
        $this->assertContains('3', $mathTargets);
        $this->assertTrue($scorer->isAllowedTarget('3', $mathTargets));
        $this->assertSame('match', $scorer->score('3', 'ثلاثة', $mathTargets)['result']);
        $this->assertSame('match', $scorer->score('1', 'واحد', $mathTargets)['result']);
        $this->assertSame('retry', $scorer->score('3', 'تفاحة', $mathTargets)['result']);
    }

    public function test_tracing_analyzer_uses_station_path_direction_and_tolerance_from_db(): void
    {
        $repo = app(InteractiveStationConfigRepository::class);
        $analyzer = app(LetterStrokeDirectionAnalyzer::class);

        $raaGuide = $repo->traceGuide('ar-g1-letter-raa');
        $this->assertSame('top_to_bottom_arc', $raaGuide['direction']);
        $this->assertNotSame('', $raaGuide['path_d']);
        $this->assertGreaterThan(0, $raaGuide['tolerance_px']);

        $goodRaa = [
            ['x' => 70, 'y' => 30],
            ['x' => 90, 'y' => 45],
            ['x' => 105, 'y' => 70],
            ['x' => 100, 'y' => 95],
            ['x' => 70, 'y' => 110],
            ['x' => 45, 'y' => 90],
        ];
        $raaResult = $analyzer->score($goodRaa, $raaGuide);
        $this->assertSame('match', $raaResult['result']);
        $this->assertTrue($raaResult['direction_ok']);

        $mathGuide = $repo->traceGuide('ar-g1-math-number-3');
        $this->assertSame('top_to_bottom_double_curve', $mathGuide['direction']);
        $this->assertNotSame('', $mathGuide['path_d']);

        $goodThree = [
            ['x' => 55, 'y' => 34],
            ['x' => 95, 'y' => 45],
            ['x' => 85, 'y' => 78],
            ['x' => 100, 'y' => 100],
            ['x' => 60, 'y' => 126],
        ];
        $mathResult = $analyzer->score($goodThree, $mathGuide);
        $this->assertSame('match', $mathResult['result']);

        $bottomUp = [
            ['x' => 60, 'y' => 126],
            ['x' => 100, 'y' => 100],
            ['x' => 85, 'y' => 78],
            ['x' => 95, 'y' => 45],
            ['x' => 55, 'y' => 34],
        ];
        $this->assertSame('retry', $analyzer->score($bottomUp, $mathGuide)['result']);
    }

    public function test_mascot_service_uses_sonbol_prompt_from_station_rows(): void
    {
        $service = app(MascotAiFeedbackService::class);

        $raaStation1 = $service->forStation(1, 'ar-g1-letter-raa');
        $this->assertNotSame('', $raaStation1['message']);
        $this->assertSame($raaStation1['message'], $raaStation1['station_hint']);
        $this->assertTrue(
            str_contains($raaStation1['message'], 'رَ')
            || str_contains($raaStation1['message'], 'رُ')
            || str_contains($raaStation1['message'], 'رِ')
            || str_contains($raaStation1['message'], 'الحركات')
            || str_contains($raaStation1['message'], 'نسمع'),
        );

        $mathStation1 = $service->forStation(1, 'ar-g1-math-number-3');
        $this->assertTrue(
            str_contains($mathStation1['message'], 'ثلاثة')
            || str_contains($mathStation1['message'], 'التفاح')
            || str_contains($mathStation1['message'], '3'),
        );

        $mathStation2 = $service->forStation(2, 'ar-g1-math-number-3');
        $this->assertTrue(
            str_contains($mathStation2['message'], 'واحد')
            || str_contains($mathStation2['message'], 'اثنان')
            || str_contains($mathStation2['message'], 'ثلاثة')
            || str_contains($mathStation2['message'], 'نعد'),
        );
    }

    public function test_adaptive_mastery_engine_returns_db_driven_hints_per_lesson(): void
    {
        $student = Student::factory()->for(User::factory()->create())->create(['grade_level' => 1]);
        $engine = app(AdaptiveMasteryEngine::class);

        $this->assertDatabaseHas('mastery_concepts', ['code' => 'diacritic_confusion']);
        $this->assertDatabaseHas('mastery_concepts', ['code' => 'bubble_sequence']);

        $first = $engine->recordError($student, 'diacritic_confusion', [
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
        ]);
        $second = $engine->recordError($student, 'diacritic_confusion', [
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
        ]);
        $third = $engine->recordError($student, 'diacritic_confusion', [
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
        ]);

        $this->assertFalse($first['show_micro_hint']);
        $this->assertFalse($second['show_micro_hint']);
        $this->assertTrue($third['show_micro_hint']);
        $this->assertNotSame('', $third['hint_message']);
        $this->assertStringContainsString('رَ', $third['hint_message']);

        $concept = MasteryConcept::query()->where('code', 'bubble_sequence')->firstOrFail();
        $this->assertSame((int) $concept->threshold, 2);

        $mathHint = null;
        for ($i = 0; $i < 3; $i++) {
            $mathHint = $engine->recordError($student, 'bubble_sequence', [
                'lesson_key' => 'ar-g1-math-number-3',
                'station' => 2,
            ]);
        }

        $this->assertTrue($mathHint['show_micro_hint']);
        $this->assertTrue(
            str_contains($mathHint['hint_message'], '1')
            || str_contains($mathHint['hint_message'], '2')
            || str_contains($mathHint['hint_message'], '3')
            || str_contains($mathHint['hint_message'], 'عد'),
        );
        $this->assertFalse(str_contains($mathHint['hint_message'], 'رَمَل'));
    }

    private function seedInteractiveLessons(): void
    {
        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);
        $math = Subject::factory()->create([
            'code' => 'MATH',
            'grade_level' => 1,
            'name' => 'الرياضيات',
        ]);

        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);
        LearningMaterial::factory()->for($math)->create([
            'title' => NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();
        app(NumberThreeInteractiveLessonImporter::class)->import();
    }
}
