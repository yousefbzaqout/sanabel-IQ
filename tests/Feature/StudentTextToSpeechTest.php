<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentTextToSpeechTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_student_tts_endpoint_returns_mpeg_audio(): void
    {
        Storage::fake('local');

        Http::fake([
            'tahadz.com/*' => Http::response(
                ['result' => 'أَيْنَ نَذْهَبُ لِنَتَعَلَّمَ مَعَ الْأَصْدِقَاءِ؟'],
                200,
            ),
            'translate.google.com/*' => Http::response(
                hex2bin('fff384c4').str_repeat("\0", 128),
                200,
                ['Content-Type' => 'audio/mpeg'],
            ),
        ]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان', 'grade_level' => 1]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.tts', ['text' => 'أين نذهب لنتعلم مع الأصدقاء؟']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/mpeg');
        $this->assertGreaterThan(64, strlen($response->getContent()));
    }

    public function test_tts_endpoint_requires_authentication_and_text(): void
    {
        $this->get(route('student.tts', ['text' => 'مرحبا']))
            ->assertRedirect(route('login'));

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 1]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('student.tts'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }
}
