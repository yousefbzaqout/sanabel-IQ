<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Student\InteractiveLessonDemo;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EndToEndInteractiveLessonFlowTest extends TestCase
{
    use RefreshDatabase;

    private const LESSON_KEY = 'ar-g1-letter-raa';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_student_completes_letter_raa_lesson_and_analytics_aggregate_for_parent_and_teacher(): void
    {
        [$parent, $student, $admin] = $this->seedContext();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get('/student/interactive-lesson/'.self::LESSON_KEY)
            ->assertOk()
            ->assertSee('data-lesson-demo="'.self::LESSON_KEY.'"', false)
            ->assertSee('data-lesson-station="1"', false);

        $this->actingAs($parent);
        session(['active_student_id' => $student->id]);

        $component = Livewire::test(InteractiveLessonDemo::class, ['lessonKey' => self::LESSON_KEY])
            ->assertSet('currentStation', 1)
            ->assertSet('lessonKey', self::LESSON_KEY);

        $stationTimes = [
            1 => 40,
            2 => 35,
            3 => 30,
            4 => 50,
            5 => 45,
            6 => 40,
        ];

        foreach ($stationTimes as $station => $timeSpent) {
            $component->assertSet('currentStation', $station);

            if ($station === 1) {
                $this->postAnalytics($parent, $student, [
                    'event_type' => 'voice_attempt',
                    'station' => 1,
                    'concept_key' => 'diacritic_confusion',
                    'payload' => ['pronunciation_score' => 92],
                ]);
            }

            if ($station === 4) {
                $this->postAnalytics($parent, $student, [
                    'event_type' => 'trace_attempt',
                    'station' => 4,
                    'concept_key' => 'incomplete_trace',
                    'payload' => [
                        'stroke_accuracy' => 88,
                        'path_precision' => 81,
                    ],
                ]);
            }

            if ($station === 5) {
                $this->postAnalytics($parent, $student, [
                    'event_type' => 'discovery_attempt',
                    'station' => 5,
                    'concept_key' => 'discovery',
                    'payload' => ['first_attempt' => true, 'success' => true],
                ]);
            }

            if ($station === 6) {
                $this->postAnalytics($parent, $student, [
                    'event_type' => 'quiz_attempt',
                    'station' => 6,
                    'concept_key' => 'quiz',
                    'payload' => ['first_attempt' => true, 'success' => true],
                ]);
            }

            $this->postAnalytics($parent, $student, [
                'event_type' => 'station_complete',
                'station' => $station,
                'concept_key' => 'station_'.$station,
                'payload' => [
                    'time_spent' => $timeSpent,
                    'mastery_score' => 80 + $station,
                ],
            ]);

            if ($station < 6) {
                $component->call('nextStation')->assertSet('currentStation', $station + 1);
            }
        }

        $totalLessonTime = array_sum($stationTimes);

        $this->postAnalytics($parent, $student, [
            'event_type' => 'lesson_complete',
            'station' => null,
            'concept_key' => 'lesson',
            'payload' => [
                'time_spent' => $totalLessonTime,
                'mastery_score' => 90,
            ],
        ]);

        $this->assertSame(1, InteractiveLesson::query()->where('lesson_key', self::LESSON_KEY)->count());
        $this->assertGreaterThanOrEqual(
            10,
            LessonAnalytic::query()->where('student_id', $student->id)->where('lesson_key', self::LESSON_KEY)->count(),
        );
        $this->assertDatabaseHas('lesson_analytics', [
            'student_id' => $student->id,
            'lesson_key' => self::LESSON_KEY,
            'event_type' => 'lesson_complete',
        ]);

        $parentAnalytics = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('parent.mastery-analytics.data', $student));

        $parentAnalytics->assertOk()
            ->assertJsonPath('student_id', $student->id)
            ->assertJsonPath('overview.completion_rate', 100)
            ->assertJsonPath('overview.average_mastery_score', 90)
            ->assertJsonPath('overview.total_time_spent_seconds', $totalLessonTime)
            ->assertJsonPath('time_by_lesson.'.self::LESSON_KEY, $totalLessonTime)
            ->assertJsonPath('time_by_station.1', 40)
            ->assertJsonPath('time_by_station.4', 50)
            ->assertJsonPath('stations.voice.pronunciation_score', 92)
            ->assertJsonPath('stations.voice.attempts', 1)
            ->assertJsonPath('stations.tracing.stroke_accuracy', 88)
            ->assertJsonPath('stations.tracing.path_precision', 81)
            ->assertJsonPath('stations.quiz_discovery.first_attempt_success_rate', 100);

        $teacherAnalytics = $this->actingAs($admin)
            ->getJson(route('admin.mastery-analytics.data'));

        $teacherAnalytics->assertOk()
            ->assertJsonPath('overview.students_tracked', 1)
            ->assertJsonPath('overview.completion_rate', 100)
            ->assertJsonPath('overview.average_mastery_score', 90)
            ->assertJsonPath('overview.total_time_spent_seconds', $totalLessonTime)
            ->assertJsonPath('stations.voice.attempts', 1)
            ->assertJsonPath('stations.tracing.stroke_accuracy', 88)
            ->assertJsonPath('stations.quiz_discovery.first_attempt_success_rate', 100);
    }

    /**
     * @param  array{
     *     event_type: string,
     *     station: int|null,
     *     concept_key: string,
     *     payload: array<string, mixed>
     * }  $body
     */
    private function postAnalytics(User $parent, Student $student, array $body): void
    {
        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.interactive-lesson.analytics.store', self::LESSON_KEY), $body)
            ->assertStatus(202)
            ->assertJsonPath('queued', true);
    }

    /**
     * @return array{0: User, 1: Student, 2: User}
     */
    private function seedContext(): array
    {
        $parent = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
            'icon' => '🔤',
        ]);

        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
            'order_column' => 1,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();

        return [$parent, $student, $admin];
    }
}
