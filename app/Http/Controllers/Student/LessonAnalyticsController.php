<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyticsEventJob;
use App\Models\Student;
use App\Services\Lessons\LessonAnalyticsRecorder;
use App\Support\Lessons\InteractiveLessonCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LessonAnalyticsController extends Controller
{
    public function store(
        Request $request,
        string $lessonKey,
    ): JsonResponse {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->findAccessibleStudentOrFail($activeStudentId);

        if (! InteractiveLessonCatalog::exists($lessonKey)) {
            throw new NotFoundHttpException('Interactive lesson not found.');
        }

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:'.implode(',', LessonAnalyticsRecorder::ALLOWED_EVENT_TYPES)],
            'concept_key' => ['required', 'string', 'max:64'],
            'station' => ['nullable', 'integer', 'min:1', 'max:6'],
            'error_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'payload' => ['nullable', 'array'],
        ]);

        $event = ProcessAnalyticsEventJob::validateEvent([
            'student_id' => $student->id,
            'lesson_key' => $lessonKey,
            'event_type' => (string) $validated['event_type'],
            'concept_key' => (string) $validated['concept_key'],
            'station' => isset($validated['station']) ? (int) $validated['station'] : null,
            'error_count' => (int) ($validated['error_count'] ?? 0),
            'payload' => is_array($validated['payload'] ?? null) ? $validated['payload'] : [],
        ]);

        ProcessAnalyticsEventJob::dispatch($event);

        return response()->json([
            'queued' => true,
            'lesson_key' => $lessonKey,
            'event_type' => $event['event_type'],
            'station' => $event['station'] ?? null,
        ], 202);
    }
}
