<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonDemo;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveLessonDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_letter_raa_lesson_demo_route_loads_six_dedicated_stations(): void
    {
        [$parent, $student, $material] = $this->seedContext();

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/lesson-demo/letter-raa');

        $response->assertOk();
        $response->assertSee('data-lesson-demo="ar-g1-letter-raa"', false);

        foreach ([1, 2, 3, 4, 5, 6] as $station) {
            $response->assertSee('data-lesson-station="'.$station.'"', false);
        }

        // Six navigation tabs
        $response->assertSee('data-station-tab="1"', false);
        $response->assertSee('data-station-tab="2"', false);
        $response->assertSee('data-station-tab="3"', false);
        $response->assertSee('data-station-tab="4"', false);
        $response->assertSee('data-station-tab="5"', false);
        $response->assertSee('data-station-tab="6"', false);

        // Station 1 — diacritics only (no bubble mechanic here)
        $response->assertSee('الحركات الثلاث', false);
        $response->assertSee('data-diacritic="رَ"', false);
        $response->assertSee('data-diacritic="رُ"', false);
        $response->assertSee('data-diacritic="رِ"', false);
        $response->assertSee('رَسّام', false);
        $response->assertSee('رَايَة', false);
        $response->assertSee('رَمَل', false);

        // Station 2 — dedicated bubble pop
        $response->assertSee('فرقعة الفقاعات', false);
        $response->assertSee('data-bubble-mechanic', false);
        $response->assertSee('data-sound-bubble="رَ"', false);
        $response->assertSee('data-sound-bubble="مَ"', false);
        $response->assertSee('data-sound-bubble="لْ"', false);

        // Station 3 — positions
        $response->assertSee('مواقع الحرف', false);
        $response->assertSee('data-position-card="begin"', false);
        $response->assertSee('data-position-card="middle"', false);
        $response->assertSee('data-position-card="end"', false);
        $response->assertSee('مَرْكَب', false);
        $response->assertSee('جَزَر', false);

        // Station 4 — tracing
        $response->assertSee('التتبع بالإصبع', false);
        $response->assertSee('data-trace-canvas', false);
        $response->assertSee('data-sonbol-slider', false);

        // Station 5 — sand scratch
        $response->assertSee('الاستكشاف البيئي', false);
        $response->assertSee('data-sand-scratch', false);
        $response->assertSee('بيارة', false);
        $response->assertSee('رُمّان', false);

        // Station 6 — little teacher + CTA
        $response->assertSee('المعلم الصغير', false);
        $response->assertSee('جاهز للاختبار يا بطل!', false);
        $response->assertSee(
            'href="'.route('student.materials.quiz', $material).'"',
            false,
        );
    }

    public function test_interactive_lesson_demo_transitions_through_six_stations(): void
    {
        [$parent, $student] = $this->seedContext();

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        Livewire::test(InteractiveLessonDemo::class)
            ->assertSet('currentStation', 1)
            ->call('nextStation')
            ->assertSet('currentStation', 2)
            ->call('nextStation')
            ->assertSet('currentStation', 3)
            ->call('nextStation')
            ->assertSet('currentStation', 4)
            ->call('nextStation')
            ->assertSet('currentStation', 5)
            ->call('nextStation')
            ->assertSet('currentStation', 6)
            ->call('nextStation')
            ->assertSet('currentStation', 6)
            ->call('previousStation')
            ->assertSet('currentStation', 5)
            ->call('goToStation', 1)
            ->assertSet('currentStation', 1);
    }

    public function test_letter_raa_lesson_demo_requires_authentication(): void
    {
        $this->get('/student/lesson-demo/letter-raa')
            ->assertRedirect(route('login'));
    }

    /**
     * @return array{0: User, 1: Student, 2: LearningMaterial}
     */
    private function seedContext(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $subject = Subject::factory()->create([
            'code' => 'AR-RAA',
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
