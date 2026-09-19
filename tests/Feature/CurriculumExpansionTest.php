<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Support\Lessons\InteractiveLessonCatalog;
use App\Support\Lessons\SurahFatihaLessonDefinition;
use App\Support\Lessons\PalestineFlagLessonDefinition;
use Database\Seeders\Grade1Semester1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade1_seeder_creates_islamic_and_national_education_interactive_lessons(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $fatihaMaterial = LearningMaterial::query()
            ->where('title', SurahFatihaLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->firstOrFail();
        $flagMaterial = LearningMaterial::query()
            ->where('title', PalestineFlagLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->firstOrFail();

        $fatiha = InteractiveLesson::query()
            ->where('lesson_key', 'ar-g1-islamic-surah-fatiha')
            ->first();
        $flag = InteractiveLesson::query()
            ->where('lesson_key', 'ar-g1-civics-palestine-flag')
            ->first();

        $this->assertNotNull($fatiha);
        $this->assertNotNull($flag);
        $this->assertTrue($fatiha->learningMaterial->is($fatihaMaterial));
        $this->assertTrue($flag->learningMaterial->is($flagMaterial));
        $this->assertSame('ISLAM', $fatiha->subject_code);
        $this->assertSame('SOCIAL', $flag->subject_code);
        $this->assertSame('published', $fatiha->status);
        $this->assertSame('published', $flag->status);
        $this->assertSame('سورة الفاتحة', $fatiha->title);
        $this->assertSame('علم بلادي فلسطين', $flag->title);

        $this->assertLessonHasSixValidStations($fatiha);
        $this->assertLessonHasSixValidStations($flag);

        $fatihaStations = $fatiha->stations()->orderBy('station_number')->get();
        $this->assertSame('بِسْمِ', $fatihaStations[0]->config['tabs'][0]['glyph'] ?? null);
        $this->assertSame(
            ['الْحَمْدُ', 'لِلَّهِ', 'رَبِّ'],
            array_column($fatihaStations[1]->config['syllables'] ?? [], 'glyph'),
        );

        $flagStations = $flag->stations()->orderBy('station_number')->get();
        $this->assertSame('flag_parts', $flagStations[2]->config['mode'] ?? null);
        $this->assertTrue(collect($flagStations[4]->config['hotspots'] ?? [])->contains(
            fn (array $item): bool => ($item['emoji'] ?? '') === '🇵🇸' && ($item['correct'] ?? false) === true,
        ));
        $this->assertSame($flagMaterial->id, $flagStations[5]->config['quiz_learning_material_id'] ?? null);
    }

    public function test_catalog_loads_expanded_lessons_from_database(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        foreach (['ar-g1-islamic-surah-fatiha', 'ar-g1-civics-palestine-flag'] as $lessonKey) {
            $payload = InteractiveLessonCatalog::get($lessonKey);
            $this->assertSame('database', $payload['source']);
            $this->assertSame($lessonKey, $payload['lesson_key']);
            $this->assertCount(6, $payload['stations']);
            $this->assertSame('variant_matrix', $payload['stations'][1]['type']);
            $this->assertSame('guided_demo', $payload['stations'][6]['type']);
        }
    }

    public function test_expanded_lessons_scratch_station_uses_lesson_specific_content(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $parent = \App\Models\User::factory()->create();
        $student = \App\Models\Student::factory()->for($parent)->create(['grade_level' => 1]);

        $fatiha = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.interactive-lesson.show', 'ar-g1-islamic-surah-fatiha'));

        $fatiha->assertOk();
        $fatiha->assertDontSee('بيارة الرمان', false);
        $fatiha->assertDontSee('رُمّان', false);
        $fatiha->assertSee('مسجد', false);
        $fatiha->assertSee('اكتشف المسجد', false);
        $fatiha->assertSee('data-target-word', false);
        $fatiha->assertSee('الْحَمْدُلِلَّهِرَبِّ', false);
        $fatiha->assertDontSee('data-target-word">رَمَل', false);
        $fatiha->assertSee('أحسنت! أكملت الترتيب', false);

        $flag = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.interactive-lesson.show', 'ar-g1-civics-palestine-flag'));

        $flag->assertOk();
        $flag->assertDontSee('بيارة الرمان', false);
        $flag->assertDontSee('رُمّان', false);
        $flag->assertSee('العلم', false);
        $flag->assertSee('اكتشف رموز الوطن', false);
    }

    public function test_curriculum_expansion_seeders_are_idempotent(): void
    {
        $this->seed(Grade1Semester1Seeder::class);
        $this->seed(Grade1Semester1Seeder::class);

        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', 'ar-g1-islamic-surah-fatiha')->count());
        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', 'ar-g1-civics-palestine-flag')->count());
        $this->assertSame(
            6,
            InteractiveLessonStation::query()
                ->whereHas('interactiveLesson', fn ($q) => $q->where('lesson_key', 'ar-g1-islamic-surah-fatiha'))
                ->count(),
        );
        $this->assertSame(
            6,
            InteractiveLessonStation::query()
                ->whereHas('interactiveLesson', fn ($q) => $q->where('lesson_key', 'ar-g1-civics-palestine-flag'))
                ->count(),
        );
    }

    private function assertLessonHasSixValidStations(InteractiveLesson $lesson): void
    {
        $stations = $lesson->stations()->orderBy('station_number')->get();
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
            $this->assertNotNull($station, "Missing station {$number} for {$lesson->lesson_key}");
            $this->assertSame($type, $station->station_type);
            $this->assertIsArray($station->config);
            $this->assertNotSame([], $station->config);
        }
    }
}
