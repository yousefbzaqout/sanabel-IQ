<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\Grade1Semester1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Grade1CurriculumIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_1_curriculum_seeds_correctly(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $subjects = Subject::query()->where('grade_level', 1)->orderBy('id')->get();

        $this->assertGreaterThanOrEqual(4, $subjects->count());
        $this->assertTrue($subjects->contains(fn (Subject $s): bool => $s->code === 'AR'));
        $this->assertTrue($subjects->contains(fn (Subject $s): bool => $s->code === 'MATH'));
        $this->assertTrue($subjects->contains(fn (Subject $s): bool => $s->code === 'ISLAM'));
        $this->assertTrue($subjects->contains(fn (Subject $s): bool => $s->code === 'SOCIAL'));

        $arabic = Subject::query()->where(['code' => 'AR', 'grade_level' => 1])->firstOrFail();
        $this->assertSame('اللغة العربية', $arabic->name);

        $lessons = LearningMaterial::query()
            ->where('subject_id', $arabic->id)
            ->where('is_published', true)
            ->get();

        $this->assertGreaterThanOrEqual(10, $lessons->count());
        $this->assertTrue($lessons->contains(fn (LearningMaterial $m): bool => str_contains($m->title, 'حرف الراء')));
        $this->assertTrue($lessons->contains(fn (LearningMaterial $m): bool => str_contains($m->title, 'التهيئة')));

        foreach ($lessons as $lesson) {
            $this->assertNotSame('', trim($lesson->title));
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $lesson->title);
        }

        $questionCount = Question::query()
            ->whereIn('learning_material_id', $lessons->pluck('id'))
            ->count();

        $this->assertGreaterThanOrEqual(20, $questionCount);

        // Idempotency: re-run does not duplicate.
        $this->seed(Grade1Semester1Seeder::class);

        $this->assertSame(
            $subjects->count(),
            Subject::query()->where('grade_level', 1)->count(),
        );
        $this->assertSame(
            $lessons->count(),
            LearningMaterial::query()->where('subject_id', $arabic->id)->count(),
        );
        $this->assertSame(
            $questionCount,
            Question::query()->whereIn('learning_material_id', $lessons->pluck('id'))->count(),
        );
    }

    public function test_questions_have_valid_audio_prompts(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $materialIds = LearningMaterial::query()
            ->whereHas('subject', fn ($q) => $q->where('grade_level', 1))
            ->pluck('id');

        $questions = Question::query()
            ->with('options')
            ->whereIn('learning_material_id', $materialIds)
            ->get();

        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            $this->assertNotSame('', trim($question->prompt));
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $question->prompt);
            // TTS-friendly: no bare URLs / HTML noise in prompts.
            $this->assertDoesNotMatchRegularExpression('/https?:\/\//i', $question->prompt);
            $this->assertDoesNotMatchRegularExpression('/<[^>]+>/', $question->prompt);

            $this->assertContains(
                $question->type,
                [QuestionType::Mcq, QuestionType::TrueFalse],
            );

            $this->assertGreaterThanOrEqual(2, $question->options->count());

            $correctCount = $question->options->where('is_correct', true)->count();
            $this->assertSame(1, $correctCount);

            foreach ($question->options as $option) {
                $this->assertNotSame('', trim($option->option_text));
            }
        }
    }

    public function test_student_learning_map_renders_seeded_subjects(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'أحمد',
            'grade_level' => 1,
        ]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('data-learning-map', false);
        $response->assertSee('اللغة العربية');
        $response->assertSee('الرياضيات');
        $response->assertSee('التربية الإسلامية');
        $response->assertSee('التنشئة الوطنية والاجتماعية');
        $response->assertSee('data-map-node', false);
        $response->assertSee('data-node-state="available"', false);
    }

    public function test_letter_lesson_questions_match_fixture_order_without_duplicates(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $material = LearningMaterial::query()
            ->where('title', 'الدرس الأول: حرف الراء')
            ->firstOrFail();

        // Inject a stale orphan question that must be pruned on re-seed.
        Question::query()->create([
            'learning_material_id' => $material->id,
            'type' => QuestionType::Mcq,
            'prompt' => 'سؤال يتيم يجب حذفه',
            'explanation' => '',
            'points' => 10,
            'order_column' => 1,
        ]);

        $this->seed(Grade1Semester1Seeder::class);

        $material->refresh();
        $orders = $material->questions()->orderBy('order_column')->orderBy('id')->pluck('order_column')->all();
        $prompts = $material->questions()->orderBy('order_column')->orderBy('id')->pluck('prompt')->all();

        $this->assertSame([0, 1], $orders);
        $this->assertCount(2, $prompts);
        $this->assertSame(count($orders), count(array_unique($orders)));
        $this->assertSame('أي كلمة تبدأ بحرف ر؟', $prompts[0]);
        $this->assertSame('حرف ر من حروف اللغة العربية الجميلة.', $prompts[1]);
        $this->assertFalse($material->questions()->where('prompt', 'سؤال يتيم يجب حذفه')->exists());
    }

    public function test_learning_map_ignores_mock_demo_materials_for_subject_completion(): void
    {
        $this->seed(Grade1Semester1Seeder::class);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 1]);

        $arabic = Subject::query()->where(['code' => 'AR', 'grade_level' => 1])->firstOrFail();

        LearningMaterial::factory()->published()->create([
            'subject_id' => $arabic->id,
            'title' => 'تجريبي G1: مادة وهمية',
            'order_column' => 999,
            'xp_reward' => 10,
        ]);

        $curriculumMaterials = $arabic->learningMaterials()
            ->published()
            ->where('title', 'not like', 'تجريبي%')
            ->get();

        $this->assertNotEmpty($curriculumMaterials);

        $scoring = app(\App\Services\Gameplay\QuizScoringService::class);

        foreach ($curriculumMaterials as $material) {
            $questions = $material->questions()->with('options')->get();
            if ($questions->isEmpty()) {
                continue;
            }

            $scoring->submit($material, $student, $questions->map(
                fn ($question): array => [
                    'question_id' => $question->id,
                    'selected_option_id' => $question->options->firstWhere('is_correct', true)->id,
                ],
            )->all());
        }

        $nodes = collect(app(\App\Services\Student\LearningMapService::class)->buildNodes($student->fresh()))
            ->keyBy('title');

        $this->assertSame('completed', $nodes['اللغة العربية']['state']);
        $this->assertSame('available', $nodes['الرياضيات']['state']);
    }
}
