<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\Grade1Semester1Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreRenderedAudioPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(BadgeSeeder::class);
    }

    public function test_grade_1_questions_have_associated_mp3_audio_paths(): void
    {
        Http::fake([
            'tahadz.com/*' => Http::response(
                ['result' => 'أَيْنَ نَذْهَبُ لِنَتَعَلَّمَ مَعَ الْأَصْدِقَاءِ؟'],
                200,
                ['Content-Type' => 'application/json'],
            ),
            'translate.google.com/*' => Http::response(
                hex2bin('fff384c4').str_repeat("\0", 256),
                200,
                ['Content-Type' => 'audio/mpeg'],
            ),
        ]);

        $this->seed(Grade1Semester1Seeder::class);

        $exitCode = Artisan::call('curriculum:generate-audio', [
            '--grade' => 1,
        ]);

        $this->assertSame(0, $exitCode);

        $questions = Question::query()
            ->whereHas('learningMaterial.subject', fn ($q) => $q->where('grade_level', 1))
            ->with('options')
            ->get();

        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            $this->assertNotNull($question->audio_path);
            $this->assertStringStartsWith('audio/grade1/q_', $question->audio_path);
            $this->assertStringEndsWith('_text.mp3', $question->audio_path);
            $this->assertTrue(
                Storage::disk('public')->exists($question->audio_path),
                "Missing audio file for question {$question->id}: {$question->audio_path}",
            );

            foreach ($question->options as $option) {
                $this->assertNotNull($option->audio_path);
                $this->assertStringStartsWith('audio/grade1/opt_', $option->audio_path);
                $this->assertTrue(Storage::disk('public')->exists($option->audio_path));
            }
        }

        $materials = LearningMaterial::query()
            ->whereHas('subject', fn ($q) => $q->where('grade_level', 1))
            ->get();

        foreach ($materials as $material) {
            $this->assertNotNull($material->audio_path);
            $this->assertTrue(Storage::disk('public')->exists($material->audio_path));
        }
    }

    public function test_quiz_runner_renders_static_audio_player_when_mp3_exists(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $subject = Subject::factory()->create([
            'code' => 'AR-TEST',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);

        $material = LearningMaterial::factory()->for($subject)->create([
            'title' => 'التهيئة',
            'is_published' => true,
            'audio_path' => 'audio/grade1/material_1_title.mp3',
        ]);

        $prompt = 'أين نذهب لنتعلم مع الأصدقاء؟';
        $audioPath = 'audio/grade1/q_999_text.mp3';

        Storage::disk('public')->put($audioPath, hex2bin('fff384c4').str_repeat("\0", 128));

        $question = Question::factory()->for($material)->create([
            'prompt' => $prompt,
            'audio_path' => $audioPath,
            'order_column' => 0,
        ]);

        $question->options()->create([
            'option_text' => '🏫 المدرسة',
            'is_correct' => true,
            'order_column' => 0,
            'audio_path' => 'audio/grade1/opt_1.mp3',
        ]);
        Storage::disk('public')->put('audio/grade1/opt_1.mp3', hex2bin('fff384c4').str_repeat("\0", 64));

        $question->options()->create([
            'option_text' => '🛒 السوق',
            'is_correct' => false,
            'order_column' => 1,
            'audio_path' => 'audio/grade1/opt_2.mp3',
        ]);
        Storage::disk('public')->put('audio/grade1/opt_2.mp3', hex2bin('fff384c4').str_repeat("\0", 64));

        $staticUrl = Storage::disk('public')->url($audioPath);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.materials.quiz', $material));

        $response->assertOk();
        $response->assertSee('data-static-audio', false);
        $response->assertSee($staticUrl, false);
        $response->assertSee('playStatic', false);
        $response->assertSee($prompt);
    }
}
