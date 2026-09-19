<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\MasteryConcept;
use App\Models\Subject;
use App\Services\AdaptiveMasteryEngine;
use App\Services\Lessons\LessonEngineCache;
use App\Support\Lessons\InteractiveLessonCatalog;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\MasteryConceptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        Cache::flush();
    }

    public function test_catalog_caches_station_payload_and_reduces_queries_on_second_load(): void
    {
        $this->seedLetterRaa();

        $lesson = InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->firstOrFail();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $first = InteractiveLessonCatalog::get('ar-g1-letter-raa');
        $firstCount = count(DB::getQueryLog());

        $this->assertSame('database', $first['source']);
        $this->assertCount(6, $first['stations']);
        $this->assertTrue(Cache::has(LessonEngineCache::stationsKey($lesson->id)));
        $this->assertTrue(Cache::has(LessonEngineCache::lessonPayloadKey('ar-g1-letter-raa')));

        DB::flushQueryLog();
        $second = InteractiveLessonCatalog::get('ar-g1-letter-raa');
        $secondCount = count(DB::getQueryLog());

        $this->assertSame($first['title'], $second['title']);
        $this->assertSame($first['stations'][1]['config'], $second['stations'][1]['config']);
        $this->assertLessThan($firstCount, $secondCount);
        $this->assertSame(0, $secondCount);
    }

    public function test_updating_lesson_or_station_via_model_invalidates_lesson_cache(): void
    {
        $this->seedLetterRaa();
        $lesson = InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->firstOrFail();

        InteractiveLessonCatalog::get('ar-g1-letter-raa');
        $this->assertTrue(Cache::has(LessonEngineCache::stationsKey($lesson->id)));

        $lesson->forceFill(['title' => 'حرف الراء — محدّث'])->save();

        $this->assertFalse(Cache::has(LessonEngineCache::stationsKey($lesson->id)));
        $this->assertFalse(Cache::has(LessonEngineCache::lessonPayloadKey('ar-g1-letter-raa')));

        InteractiveLessonCatalog::get('ar-g1-letter-raa');
        $this->assertTrue(Cache::has(LessonEngineCache::stationsKey($lesson->id)));

        $station = InteractiveLessonStation::query()
            ->where('interactive_lesson_id', $lesson->id)
            ->where('station_number', 1)
            ->firstOrFail();

        $station->forceFill(['title' => 'محطة محدّثة'])->save();

        $this->assertFalse(Cache::has(LessonEngineCache::stationsKey($lesson->id)));
        $this->assertFalse(Cache::has(LessonEngineCache::lessonPayloadKey('ar-g1-letter-raa')));
    }

    public function test_mastery_concepts_are_cached_and_invalidated_on_update(): void
    {
        $this->seed(MasteryConceptSeeder::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $first = LessonEngineCache::allConcepts();
        $firstCount = count(DB::getQueryLog());

        $this->assertTrue($first->isNotEmpty());
        $this->assertTrue(Cache::has(LessonEngineCache::CONCEPTS_ALL));

        DB::flushQueryLog();
        $second = LessonEngineCache::allConcepts();
        $secondCount = count(DB::getQueryLog());

        $this->assertSame($first->count(), $second->count());
        $this->assertLessThan($firstCount, $secondCount);
        $this->assertSame(0, $secondCount);

        $concept = MasteryConcept::query()->where('code', AdaptiveMasteryEngine::CONCEPT_DIACRITIC)->firstOrFail();
        $engine = app(AdaptiveMasteryEngine::class);

        $this->assertSame($concept->hint_template, $engine->hintMessage(AdaptiveMasteryEngine::CONCEPT_DIACRITIC));

        $concept->forceFill(['hint_template' => 'تلميح مخزّن جديد'])->save();
        $this->assertFalse(Cache::has(LessonEngineCache::CONCEPTS_ALL));

        $this->assertSame('تلميح مخزّن جديد', $engine->hintMessage(AdaptiveMasteryEngine::CONCEPT_DIACRITIC));
        $this->assertTrue(Cache::has(LessonEngineCache::CONCEPTS_ALL));
    }

    private function seedLetterRaa(): void
    {
        $parent = \App\Models\User::factory()->create();
        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);
        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);
        unset($parent);

        app(LetterRaaInteractiveLessonImporter::class)->import();
    }
}
