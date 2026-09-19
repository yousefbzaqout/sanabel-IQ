<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\Grade1Semester1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioSystemDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        $this->seed(Grade1Semester1Seeder::class);
    }

    public function test_student_and_quiz_layouts_include_audio_assets_and_triggers(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $dashboard = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('data-student-layout', false);
        $dashboard->assertSee('data-sanabel-audio-boot', false);
        $dashboard->assertSee('data-audio-toggle', false);
        $dashboard->assertSee('sanabel_audio_muted', false);
        $dashboard->assertSee('$store.audio', false);
        $dashboard->assertSee('data-mascot-name="sonbol"', false);
        $dashboard->assertSee('سماع الرسالة');

        $material = LearningMaterial::query()
            ->whereHas('subject', fn ($q) => $q->where('grade_level', 1))
            ->where('is_published', true)
            ->orderBy('order_column')
            ->firstOrFail();

        $quiz = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $quiz->assertOk();
        $quiz->assertSee('data-quiz-immersive', false);
        $quiz->assertSee('data-sanabel-audio-boot', false);
        $quiz->assertSee('سماع السؤال');
        $quiz->assertSee('$store.audio', false);
        $quiz->assertSee('data-tts-text', false);
        $quiz->assertSee('window.livewireScriptConfig', false);
    }

    public function test_quiz_questions_expose_non_empty_tts_speech_text(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'أحمد',
            'grade_level' => 1,
        ]);

        $material = LearningMaterial::query()
            ->whereHas('subject', fn ($q) => $q->where('grade_level', 1))
            ->where('is_published', true)
            ->whereHas('questions')
            ->orderBy('order_column')
            ->firstOrFail();

        $questions = Question::query()
            ->where('learning_material_id', $material->id)
            ->orderBy('order_column')
            ->get();

        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            $this->assertNotSame('', trim($question->prompt));
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $question->prompt);
        }

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $response->assertOk();

        $firstPrompt = (string) $questions->first()->prompt;
        $response->assertSee($firstPrompt);
        $response->assertSee('data-tts-text="'.e($firstPrompt).'"', false);
    }
}
