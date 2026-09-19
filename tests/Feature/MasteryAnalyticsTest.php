<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasteryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_mastery_analytics_endpoint_aggregates_overview_and_station_skills(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->seedAnalyticsForStudent($student);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('parent.mastery-analytics.data', $student));

        $response->assertOk()
            ->assertJsonPath('student_id', $student->id)
            ->assertJsonPath('overview.completion_rate', 50)
            ->assertJsonPath('overview.average_mastery_score', 80)
            ->assertJsonPath('overview.total_time_spent_seconds', 240)
            ->assertJsonPath('time_by_lesson.ar-g1-letter-raa', 180)
            ->assertJsonPath('time_by_lesson.ar-g1-math-number-3', 60)
            ->assertJsonPath('time_by_station.1', 40)
            ->assertJsonPath('time_by_station.4', 70)
            ->assertJsonPath('stations.voice.pronunciation_score', 90)
            ->assertJsonPath('stations.voice.attempts', 2)
            ->assertJsonPath('stations.tracing.stroke_accuracy', 85)
            ->assertJsonPath('stations.tracing.path_precision', 78)
            ->assertJsonPath('stations.quiz_discovery.first_attempt_success_rate', 50)
            ->assertJsonPath('ai_interventions.total_hints', 2)
            ->assertJsonPath('ai_interventions.by_concept.diacritic_confusion', 1)
            ->assertJsonPath('ai_interventions.by_concept.incomplete_trace', 1);
    }

    public function test_parent_mastery_analytics_dashboard_renders_for_authorized_parent(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'ليان']);

        $this->seedAnalyticsForStudent($student);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.mastery-analytics.show', $student));

        $response->assertOk()
            ->assertSee('ليان', false)
            ->assertSee('إتقان الدروس', false)
            ->assertSee('تدخلات سنبل', false);
    }

    public function test_parent_cannot_view_another_parents_mastery_analytics(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $student = Student::factory()->for($owner)->create();

        $this->actingAs($other)
            ->getJson(route('parent.mastery-analytics.data', $student))
            ->assertForbidden();
    }

    public function test_teacher_mastery_analytics_endpoint_aggregates_across_students(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = User::factory()->create();
        $studentA = Student::factory()->for($parent)->create();
        $studentB = Student::factory()->for($parent)->create();

        $this->seedAnalyticsForStudent($studentA);
        LessonAnalytic::query()->create([
            'student_id' => $studentB->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'micro_hint',
            'error_count' => 3,
            'payload' => ['hint' => 'تلميح'],
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.mastery-analytics.data'));

        $response->assertOk()
            ->assertJsonPath('overview.students_tracked', 2)
            ->assertJsonPath('overview.average_mastery_score', 80)
            ->assertJsonPath('ai_interventions.total_hints', 3);
    }

    public function test_teacher_mastery_analytics_dashboard_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedAnalyticsForStudent($student);

        $this->actingAs($admin)
            ->get(route('admin.mastery-analytics.show'))
            ->assertOk()
            ->assertSee('لوحة إتقان الدروس', false)
            ->assertSee('تدخلات سنبل', false);
    }

    private function seedAnalyticsForStudent(Student $student): void
    {
        // Two lessons touched; one completed → 50% completion
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => null,
            'concept_key' => 'lesson',
            'event_type' => 'lesson_complete',
            'error_count' => 0,
            'payload' => [
                'time_spent' => 180,
                'mastery_score' => 90,
            ],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-math-number-3',
            'station' => null,
            'concept_key' => 'lesson',
            'event_type' => 'station_complete',
            'error_count' => 0,
            'payload' => [
                'time_spent' => 60,
                'mastery_score' => 70,
                'completed' => false,
            ],
        ]);

        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
            'concept_key' => 'voice',
            'event_type' => 'station_complete',
            'error_count' => 0,
            'payload' => ['time_spent' => 40, 'mastery_score' => 88],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 4,
            'concept_key' => 'trace',
            'event_type' => 'station_complete',
            'error_count' => 0,
            'payload' => ['time_spent' => 70, 'mastery_score' => 82],
        ]);

        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'voice_attempt',
            'error_count' => 1,
            'payload' => ['pronunciation_score' => 85],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'voice_attempt',
            'error_count' => 2,
            'payload' => ['pronunciation_score' => 95],
        ]);

        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 4,
            'concept_key' => 'incomplete_trace',
            'event_type' => 'trace_attempt',
            'error_count' => 1,
            'payload' => [
                'stroke_accuracy' => 80,
                'path_precision' => 76,
            ],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 4,
            'concept_key' => 'incomplete_trace',
            'event_type' => 'trace_attempt',
            'error_count' => 2,
            'payload' => [
                'stroke_accuracy' => 90,
                'path_precision' => 80,
            ],
        ]);

        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 5,
            'concept_key' => 'discovery',
            'event_type' => 'discovery_attempt',
            'error_count' => 0,
            'payload' => ['first_attempt' => true, 'success' => true],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 6,
            'concept_key' => 'quiz',
            'event_type' => 'quiz_attempt',
            'error_count' => 1,
            'payload' => ['first_attempt' => true, 'success' => false],
        ]);

        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 1,
            'concept_key' => 'diacritic_confusion',
            'event_type' => 'micro_hint',
            'error_count' => 3,
            'payload' => ['hint' => 'فرّق بين رَ و رُ', 'mastery_concept_id' => 1],
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'station' => 4,
            'concept_key' => 'incomplete_trace',
            'event_type' => 'micro_hint',
            'error_count' => 3,
            'payload' => ['hint' => 'ابدأ من أعلى الحرف', 'mastery_concept_id' => 2],
        ]);
    }
}
