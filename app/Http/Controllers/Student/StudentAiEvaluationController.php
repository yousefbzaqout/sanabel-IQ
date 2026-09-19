<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\AdaptiveMasteryEngine;
use App\Services\Ai\ArabicPronunciationScorer;
use App\Services\Ai\LetterStrokeDirectionAnalyzer;
use App\Services\Lessons\InteractiveStationConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAiEvaluationController extends Controller
{
    public function pronunciation(
        Request $request,
        ArabicPronunciationScorer $scorer,
    ): JsonResponse {
        $validated = $request->validate([
            'target' => ['required', 'string', 'max:16'],
            'transcript' => ['required', 'string', 'max:120'],
            'lesson_key' => ['nullable', 'string', 'max:64'],
        ]);

        $allowedTargets = filled($validated['lesson_key'] ?? null)
            ? $scorer->allowedTargetsForLesson((string) $validated['lesson_key'])
            : null;

        if (! $scorer->isAllowedTarget($validated['target'], $allowedTargets)) {
            return response()->json([
                'message' => 'The target field is invalid.',
                'errors' => ['target' => ['Unsupported pronunciation target.']],
            ], 422);
        }

        return response()->json(
            $scorer->score($validated['target'], $validated['transcript'], $allowedTargets),
        );
    }

    public function stroke(
        Request $request,
        LetterStrokeDirectionAnalyzer $analyzer,
        InteractiveStationConfigRepository $stations,
    ): JsonResponse {
        $validated = $request->validate([
            'points' => ['required', 'array', 'min:4'],
            'points.*.x' => ['required', 'numeric'],
            'points.*.y' => ['required', 'numeric'],
            'lesson_key' => ['nullable', 'string', 'max:64'],
            'direction' => ['nullable', 'string', 'max:64'],
            'tolerance_px' => ['nullable', 'integer', 'min:4', 'max:80'],
        ]);

        /** @var list<array{x: float|int, y: float|int}> $points */
        $points = $validated['points'];

        $guide = [];
        if (filled($validated['lesson_key'] ?? null)) {
            $guide = $stations->traceGuide((string) $validated['lesson_key']);
        }

        if (filled($validated['direction'] ?? null)) {
            $guide['direction'] = (string) $validated['direction'];
        }

        if (isset($validated['tolerance_px'])) {
            $guide['tolerance_px'] = (int) $validated['tolerance_px'];
        }

        return response()->json($analyzer->score($points, $guide));
    }

    public function microHint(
        Request $request,
        AdaptiveMasteryEngine $engine,
    ): JsonResponse {
        /** @var Student $student */
        $student = $request->attributes->get('activeStudent');

        $validated = $request->validate([
            'concept_key' => ['required', 'string', 'max:64'],
            'lesson_key' => ['nullable', 'string', 'max:64'],
            'station' => ['nullable', 'integer', 'min:1', 'max:6'],
            'context' => ['nullable', 'array'],
        ]);

        /** @var array<string, mixed> $context */
        $context = is_array($validated['context'] ?? null) ? $validated['context'] : [];
        $context['lesson_key'] = (string) ($validated['lesson_key'] ?? $context['lesson_key'] ?? 'ar-g1-letter-raa');

        if (isset($validated['station'])) {
            $context['station'] = (int) $validated['station'];
        }

        return response()->json(
            $engine->recordError($student, (string) $validated['concept_key'], $context),
        );
    }
}
