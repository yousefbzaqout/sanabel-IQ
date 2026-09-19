<?php

declare(strict_types=1);

namespace App\Services\Lessons;

use App\Models\InteractiveLesson;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Services\Analytics\MasteryAnalyticsService;
use InvalidArgumentException;

final class LessonAnalyticsRecorder
{
    /**
     * @var list<string>
     */
    public const ALLOWED_EVENT_TYPES = [
        MasteryAnalyticsService::EVENT_VOICE_ATTEMPT,
        MasteryAnalyticsService::EVENT_TRACE_ATTEMPT,
        MasteryAnalyticsService::EVENT_STATION_COMPLETE,
        MasteryAnalyticsService::EVENT_LESSON_COMPLETE,
        MasteryAnalyticsService::EVENT_QUIZ_ATTEMPT,
        MasteryAnalyticsService::EVENT_DISCOVERY_ATTEMPT,
        MasteryAnalyticsService::EVENT_MICRO_HINT,
        'error',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        Student $student,
        string $lessonKey,
        string $eventType,
        string $conceptKey,
        ?int $station = null,
        array $payload = [],
        int $errorCount = 0,
    ): LessonAnalytic {
        if (! in_array($eventType, self::ALLOWED_EVENT_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported lesson analytics event type: {$eventType}");
        }

        $interactiveLesson = InteractiveLesson::query()
            ->where('lesson_key', $lessonKey)
            ->first();

        return LessonAnalytic::query()->create([
            'student_id' => $student->id,
            'lesson_key' => $lessonKey,
            'interactive_lesson_id' => $interactiveLesson?->id,
            'learning_material_id' => $interactiveLesson?->learning_material_id,
            'station' => $station,
            'concept_key' => $conceptKey,
            'event_type' => $eventType,
            'error_count' => max(0, $errorCount),
            'payload' => $payload,
        ]);
    }
}
