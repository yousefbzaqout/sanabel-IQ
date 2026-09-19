<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Subject;
use Database\Seeders\PalestinianCurriculumPrototypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PalestinianCurriculumPrototypeSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_grade1_arabic_prototype_with_intact_foreign_keys(): void
    {
        $this->seed(PalestinianCurriculumPrototypeSeeder::class);

        $subject = Subject::query()
            ->where('code', 'AR')
            ->where('grade_level', 1)
            ->first();

        $this->assertNotNull($subject);
        $this->assertStringContainsString('لغتنا الجميلة', (string) $subject->description);

        $lessons = InteractiveLesson::query()
            ->where('subject_code', 'AR')
            ->where('grade_level', 1)
            ->where('lesson_key', 'like', 'ar-g1-letter-%')
            ->get();

        $this->assertGreaterThanOrEqual(3, $lessons->count());

        foreach ($lessons as $lesson) {
            $this->assertNotNull($lesson->learning_material_id);
            $this->assertDatabaseHas('learning_materials', [
                'id' => $lesson->learning_material_id,
                'subject_id' => $subject->id,
                'is_published' => true,
            ]);

            $stationCount = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->count();
            $this->assertSame(6, $stationCount);

            $voice = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->where('station_type', 'variant_matrix')
                ->first();
            $this->assertNotNull($voice);
            $this->assertArrayHasKey('voice_targets', $voice->config);
            $this->assertNotEmpty($voice->config['voice_targets']);

            $stroke = InteractiveLessonStation::query()
                ->where('interactive_lesson_id', $lesson->id)
                ->where('station_type', 'trace_canvas')
                ->first();
            $this->assertNotNull($stroke);
            $this->assertNotEmpty($stroke->config['paths'][0]['d'] ?? null);

            $questions = Question::query()
                ->where('learning_material_id', $lesson->learning_material_id)
                ->count();
            $this->assertGreaterThanOrEqual(2, $questions);
        }
    }

    #[Test]
    public function it_is_idempotent_on_rerun(): void
    {
        $this->seed(PalestinianCurriculumPrototypeSeeder::class);
        $this->seed(PalestinianCurriculumPrototypeSeeder::class);

        $this->assertSame(
            1,
            InteractiveLesson::query()->where('lesson_key', 'ar-g1-letter-raa')->count(),
        );
        $this->assertSame(
            6,
            InteractiveLessonStation::query()
                ->whereHas('interactiveLesson', static fn ($q) => $q->where('lesson_key', 'ar-g1-letter-raa'))
                ->count(),
        );
    }
}
