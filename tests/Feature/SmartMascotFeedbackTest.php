<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonDemo;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\MascotAiFeedbackService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SmartMascotFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_service_selects_celebration_for_quick_high_mastery(): void
    {
        $service = new MascotAiFeedbackService;

        $feedback = $service->generate([
            'attempts' => 1,
            'time_spent' => 4,
            'mastery_score' => 95,
            'station' => 1,
        ]);

        $this->assertSame('celebrate', $feedback['tone']);
        $this->assertSame('happy', $feedback['mascot_state']);
        $this->assertNotSame('', $feedback['message']);
        $this->assertStringContainsStringIgnoringCase('بطل', $feedback['message']);
        $this->assertSame($feedback['message'], $feedback['audio_prompt']);
    }

    public function test_service_selects_encouragement_for_low_mastery_with_retries(): void
    {
        $service = new MascotAiFeedbackService;

        $feedback = $service->generate([
            'attempts' => 4,
            'time_spent' => 40,
            'mastery_score' => 35,
            'station' => 2,
        ]);

        $this->assertSame('encourage', $feedback['tone']);
        $this->assertSame('encouraging', $feedback['mascot_state']);
        $this->assertTrue(
            str_contains($feedback['message'], 'حاول')
            || str_contains($feedback['message'], 'مرة')
            || str_contains($feedback['message'], 'جداً'),
        );
    }

    public function test_service_selects_persist_celebration_after_many_attempts_then_success(): void
    {
        $service = new MascotAiFeedbackService;

        $feedback = $service->generate([
            'attempts' => 5,
            'time_spent' => 55,
            'mastery_score' => 88,
            'station' => 4,
        ]);

        $this->assertSame('persist', $feedback['tone']);
        $this->assertSame('happy', $feedback['mascot_state']);
        $this->assertTrue(
            str_contains($feedback['message'], 'مثابرة')
            || str_contains($feedback['message'], 'صبرت')
            || str_contains($feedback['message'], 'أحسنت'),
        );
    }

    public function test_interactive_lesson_demo_updates_mascot_message_from_performance(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 1]);
        $subject = Subject::factory()->create(['grade_level' => 1, 'code' => 'AR-SMART']);
        LearningMaterial::factory()->for($subject)->create([
            'title' => 'الدرس الأول: حرف الراء',
            'is_published' => true,
        ]);

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonDemo::class)
            ->assertSet('currentStation', 1)
            ->assertNotSet('mascotMessage', '')
            ->call('reportPerformance', 1, 5, 96)
            ->assertSet('mascotTone', 'celebrate')
            ->assertSet('mascotState', 'happy')
            ->call('reportPerformance', 4, 45, 30)
            ->assertSet('mascotTone', 'encourage')
            ->assertSet('mascotState', 'encouraging')
            ->call('nextStation')
            ->assertSet('currentStation', 2)
            ->assertNotSet('mascotMessage', '');
    }
}
