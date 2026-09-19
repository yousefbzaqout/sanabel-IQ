<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonRunner;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveLessonEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_letter_raa_engine_renders_five_states_with_audio_and_diacritics(): void
    {
        [$parent, $student, $material] = $this->seedLetterRaaContext();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonRunner::class, [
            'lessonKey' => 'letter-raa',
        ])
            ->assertSee('data-interactive-lesson="letter-raa"', false)
            ->assertSee('data-lesson-engine-state="1"', false)
            ->assertSee('data-lesson-engine-state="2"', false)
            ->assertSee('data-lesson-engine-state="3"', false)
            ->assertSee('data-lesson-engine-state="4"', false)
            ->assertSee('data-lesson-engine-state="5"', false)
            ->assertSee('data-diacritic="رَ"', false)
            ->assertSee('data-diacritic="رُ"', false)
            ->assertSee('data-diacritic="رِ"', false)
            ->assertSee('data-audio-trigger', false)
            ->assertSee('رَايَة', false)
            ->assertSee('مَرْكَب', false)
            ->assertSee('جَزَر', false)
            ->assertSee('data-trace-canvas', false)
            ->assertSee('رُمّان', false)
            ->assertSee('زَيْتُون', false)
            ->assertSee('رِيشَة', false)
            ->assertSee('جاهز للاختبار يا بطل!', false)
            ->assertSee(
                'href="'.route('student.materials.quiz', $material).'"',
                false,
            );
    }

    public function test_interactive_lesson_runner_transitions_through_all_five_states(): void
    {
        [$parent, $student] = $this->seedLetterRaaContext();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonRunner::class, [
            'lessonKey' => 'letter-raa',
        ])
            ->assertSet('currentState', 1)
            ->assertSee('الحركات الثلاث', false)
            ->call('nextState')
            ->assertSet('currentState', 2)
            ->assertSee('مواقع الحرف', false)
            ->call('nextState')
            ->assertSet('currentState', 3)
            ->assertSee('التتبع بالإصبع', false)
            ->call('nextState')
            ->assertSet('currentState', 4)
            ->assertSee('الاستكشاف البيئي', false)
            ->call('nextState')
            ->assertSet('currentState', 5)
            ->assertSee('المعلم الصغير', false)
            ->call('nextState')
            ->assertSet('currentState', 5)
            ->call('previousState')
            ->assertSet('currentState', 4)
            ->call('goToState', 1)
            ->assertSet('currentState', 1);
    }

    public function test_go_to_state_rejects_out_of_range_values(): void
    {
        [$parent, $student] = $this->seedLetterRaaContext();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonRunner::class, [
            'lessonKey' => 'letter-raa',
        ])
            ->call('goToState', 0)
            ->assertSet('currentState', 1)
            ->call('goToState', 9)
            ->assertSet('currentState', 1)
            ->call('goToState', 3)
            ->assertSet('currentState', 3);
    }

    /**
     * @return array{0: User, 1: Student, 2: LearningMaterial}
     */
    private function seedLetterRaaContext(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $subject = Subject::factory()->create([
            'code' => 'AR-ENGINE',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);

        $material = LearningMaterial::factory()->for($subject)->create([
            'title' => 'الدرس الأول: حرف الراء',
            'is_published' => true,
        ]);

        return [$parent, $student, $material];
    }
}
