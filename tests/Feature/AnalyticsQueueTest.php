<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessAnalyticsEventJob;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\Lessons\LetterRaaInteractiveLessonImporter;
use App\Support\Lessons\LetterRaaLessonDefinition;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnalyticsQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_analytics_endpoint_dispatches_process_analytics_event_job(): void
    {
        Queue::fake();

        [$parent, $student] = $this->seedLetterRaaContext();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.interactive-lesson.analytics.store', 'ar-g1-letter-raa'), [
                'event_type' => 'voice_attempt',
                'concept_key' => 'diacritic_confusion',
                'station' => 1,
                'payload' => ['pronunciation_score' => 91],
            ])
            ->assertStatus(202)
            ->assertJsonPath('queued', true)
            ->assertJsonPath('event_type', 'voice_attempt')
            ->assertJsonPath('lesson_key', 'ar-g1-letter-raa');

        Queue::assertPushed(ProcessAnalyticsEventJob::class, function (ProcessAnalyticsEventJob $job) use ($student): bool {
            return $job->event['student_id'] === $student->id
                && $job->event['lesson_key'] === 'ar-g1-letter-raa'
                && $job->event['event_type'] === 'voice_attempt'
                && $job->event['concept_key'] === 'diacritic_confusion'
                && ($job->event['payload']['pronunciation_score'] ?? null) === 91;
        });

        $this->assertDatabaseMissing('lesson_analytics', [
            'student_id' => $student->id,
            'event_type' => 'voice_attempt',
        ]);
    }

    public function test_processing_job_persists_lesson_analytics_record(): void
    {
        [$parent, $student] = $this->seedLetterRaaContext();
        unset($parent);

        $job = new ProcessAnalyticsEventJob([
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'event_type' => 'station_complete',
            'concept_key' => 'station_2',
            'station' => 2,
            'error_count' => 0,
            'payload' => [
                'time_spent' => 35,
                'mastery_score' => 88,
            ],
        ]);

        $job->handle(app(\App\Services\Lessons\LessonAnalyticsRecorder::class));

        $this->assertDatabaseHas('lesson_analytics', [
            'student_id' => $student->id,
            'lesson_key' => 'ar-g1-letter-raa',
            'event_type' => 'station_complete',
            'concept_key' => 'station_2',
            'station' => 2,
        ]);

        $row = LessonAnalytic::query()
            ->where('student_id', $student->id)
            ->where('event_type', 'station_complete')
            ->firstOrFail();

        $this->assertSame(35, $row->payload['time_spent'] ?? null);
        $this->assertSame(88, $row->payload['mastery_score'] ?? null);
    }

    public function test_malformed_payload_is_rejected_before_queue_dispatch(): void
    {
        Queue::fake();

        [$parent, $student] = $this->seedLetterRaaContext();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.interactive-lesson.analytics.store', 'ar-g1-letter-raa'), [
                'event_type' => 'not_a_real_event',
                'concept_key' => 'x',
            ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_job_rejects_poisoned_payload_schema(): void
    {
        $this->expectException(ValidationException::class);

        $job = new ProcessAnalyticsEventJob([
            'student_id' => 1,
            'lesson_key' => 'ar-g1-letter-raa',
            'event_type' => 'invalid_type',
            'concept_key' => 'x',
        ]);

        $job->handle(app(\App\Services\Lessons\LessonAnalyticsRecorder::class));
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function seedLetterRaaContext(): array
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'ليان',
            'grade_level' => 1,
        ]);

        $arabic = Subject::factory()->create([
            'code' => 'AR',
            'grade_level' => 1,
            'name' => 'اللغة العربية',
        ]);
        LearningMaterial::factory()->for($arabic)->create([
            'title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            'is_published' => true,
        ]);

        app(LetterRaaInteractiveLessonImporter::class)->import();

        return [$parent, $student];
    }
}
