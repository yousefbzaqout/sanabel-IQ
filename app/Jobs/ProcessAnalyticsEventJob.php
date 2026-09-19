<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Student;
use App\Services\Lessons\LessonAnalyticsRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessAnalyticsEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 15, 30];

    /**
     * @param  array{
     *     student_id: int,
     *     lesson_key: string,
     *     event_type: string,
     *     concept_key: string,
     *     station?: int|null,
     *     error_count?: int,
     *     payload?: array<string, mixed>
     * }  $event
     */
    public function __construct(public array $event)
    {
        $this->onQueue('analytics');
    }

    public function handle(LessonAnalyticsRecorder $recorder): void
    {
        $validated = self::validateEvent($this->event);

        try {
            $recorder->record(
                student: Student::query()->findOrFail((int) $validated['student_id']),
                lessonKey: (string) $validated['lesson_key'],
                eventType: (string) $validated['event_type'],
                conceptKey: (string) $validated['concept_key'],
                station: isset($validated['station']) ? (int) $validated['station'] : null,
                payload: is_array($validated['payload'] ?? null) ? $validated['payload'] : [],
                errorCount: (int) ($validated['error_count'] ?? 0),
            );
        } catch (Throwable $exception) {
            Log::error('Failed to process lesson analytics event.', [
                'event' => $validated,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{
     *     student_id: int,
     *     lesson_key: string,
     *     event_type: string,
     *     concept_key: string,
     *     station?: int|null,
     *     error_count?: int,
     *     payload?: array<string, mixed>
     * }
     */
    public static function validateEvent(array $event): array
    {
        $validator = Validator::make($event, [
            'student_id' => ['required', 'integer', 'min:1'],
            'lesson_key' => ['required', 'string', 'max:64'],
            'event_type' => ['required', 'string', 'in:'.implode(',', LessonAnalyticsRecorder::ALLOWED_EVENT_TYPES)],
            'concept_key' => ['required', 'string', 'max:64'],
            'station' => ['nullable', 'integer', 'min:1', 'max:6'],
            'error_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'payload' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array{
         *     student_id: int,
         *     lesson_key: string,
         *     event_type: string,
         *     concept_key: string,
         *     station?: int|null,
         *     error_count?: int,
         *     payload?: array<string, mixed>
         * } $validated
         */
        $validated = $validator->validated();

        return $validated;
    }
}
