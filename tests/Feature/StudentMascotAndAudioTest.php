<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\View\Components\Student\Mascot;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMascotAndAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_mascot_component_renders_correct_state_and_message(): void
    {
        $html = (string) $this->blade(
            '<x-student.mascot state="happy" message="أنت رائع يا بطل!" />',
        );

        $this->assertStringContainsString('data-mascot-state="happy"', $html);
        $this->assertStringContainsString('أنت رائع يا بطل!', $html);
        $this->assertStringContainsString('data-mascot-name="sonbol"', $html);
        $this->assertStringContainsString('role="img"', $html);

        $thinking = (string) $this->blade(
            '<x-student.mascot state="thinking" message="فكّر جيداً..." />',
        );

        $this->assertStringContainsString('data-mascot-state="thinking"', $thinking);
        $this->assertStringContainsString('فكّر جيداً...', $thinking);

        $component = new Mascot(state: 'encouraging', message: 'حاول مرة أخرى!');
        $this->assertSame('encouraging', $component->state);
        $this->assertSame('حاول مرة أخرى!', $component->message);
        $this->assertContains($component->state, Mascot::STATES);
    }

    public function test_student_dashboard_includes_audio_preference_state(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('data-audio-toggle', false);
        $response->assertSee('sanabel_audio_muted', false);
        $response->assertSee('data-mascot-name="sonbol"', false);
        $response->assertSee('ليان');
    }

    public function test_mascot_rejects_invalid_state_and_falls_back_to_idle(): void
    {
        $component = new Mascot(state: 'unknown-state', message: 'مرحباً');

        $this->assertSame('idle', $component->state);

        $html = (string) $this->blade(
            '<x-student.mascot state="invalid" message="مرحباً" />',
        );

        $this->assertStringContainsString('data-mascot-state="idle"', $html);
    }
}
