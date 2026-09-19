<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonDemo;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AdaptiveMasteryEngine;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdaptiveMasteryEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_engine_tracks_error_patterns_and_triggers_micro_hint_after_threshold(): void
    {
        $student = $this->makeStudent();
        $engine = app(AdaptiveMasteryEngine::class);

        $first = $engine->recordError($student, 'diacritic_confusion', [
            'expected' => 'رَ',
            'actual' => 'رُ',
            'station' => 1,
        ]);
        $this->assertFalse($first['show_micro_hint']);
        $this->assertSame(1, $first['error_count']);

        $second = $engine->recordError($student, 'diacritic_confusion', [
            'expected' => 'رَ',
            'actual' => 'رُ',
            'station' => 1,
        ]);
        $this->assertFalse($second['show_micro_hint']);

        $third = $engine->recordError($student, 'diacritic_confusion', [
            'expected' => 'رَ',
            'actual' => 'رُ',
            'station' => 1,
        ]);
        $this->assertTrue($third['show_micro_hint']);
        $this->assertSame(3, $third['error_count']);
        $this->assertNotSame('', $third['hint_message']);
        $this->assertStringContainsString('رَ', $third['hint_message']);

        $this->assertDatabaseHas('lesson_analytics', [
            'student_id' => $student->id,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'error',
        ]);
        $this->assertGreaterThanOrEqual(3, LessonAnalytic::query()->where('student_id', $student->id)->count());
    }

    public function test_engine_tracks_bubble_and_tracing_errors_separately(): void
    {
        $student = $this->makeStudent();
        $engine = app(AdaptiveMasteryEngine::class);

        $engine->recordError($student, 'bubble_sequence', ['station' => 2]);
        $engine->recordError($student, 'bubble_sequence', ['station' => 2]);
        $bubbleHint = $engine->recordError($student, 'bubble_sequence', ['station' => 2]);

        $this->assertTrue($bubbleHint['show_micro_hint']);
        $this->assertSame('bubble_sequence', $bubbleHint['concept_key']);

        $trace = $engine->recordError($student, 'incomplete_trace', ['station' => 4]);
        $this->assertFalse($trace['show_micro_hint']);
        $this->assertSame(1, $trace['error_count']);
    }

    public function test_interactive_lesson_demo_inserts_micro_hint_before_next_station(): void
    {
        [$parent, $student] = $this->seedDemoContext();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        $component = Livewire::test(InteractiveLessonDemo::class)
            ->assertSet('showMicroHint', false)
            ->assertSet('currentStation', 1);

        $component->call('recordLessonError', 'diacritic_confusion', ['expected' => 'رَ', 'actual' => 'رُ'])
            ->call('recordLessonError', 'diacritic_confusion', ['expected' => 'رَ', 'actual' => 'رُ'])
            ->call('recordLessonError', 'diacritic_confusion', ['expected' => 'رَ', 'actual' => 'رُ'])
            ->assertSet('showMicroHint', true)
            ->assertSet('pendingHintConcept', 'diacritic_confusion')
            ->assertSee('data-micro-hint', false);

        $component->call('nextStation')
            ->assertSet('currentStation', 1)
            ->assertSet('showMicroHint', true);

        $component->call('dismissMicroHint')
            ->assertSet('showMicroHint', false)
            ->call('nextStation')
            ->assertSet('currentStation', 2);
    }

    private function makeStudent(): Student
    {
        $parent = User::factory()->create();

        return Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function seedDemoContext(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['grade_level' => 1]);
        $subject = Subject::factory()->create(['grade_level' => 1, 'code' => 'AR-ADAPT']);
        LearningMaterial::factory()->for($subject)->create([
            'title' => 'الدرس الأول: حرف الراء',
            'is_published' => true,
        ]);

        return [$parent, $student];
    }
}
