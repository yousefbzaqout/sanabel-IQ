<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Http\Requests\SubmitActivityAnswersRequest;
use App\Models\Activity;
use App\Models\Student;
use App\Services\Gameplay\ActivitySubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $activities = Activity::query()
            ->with(['parentMaterial', 'attempts' => fn ($query) => $query
                ->where('student_id', $activeStudentId)
                ->latest('completed_at')
                ->limit(1),
            ])
            ->where('student_id', $activeStudentId)
            ->where('status', ActivityStatus::Published)
            ->latest()
            ->get();

        return view('student.activities.index', [
            'student' => $student,
            'activities' => $activities,
        ]);
    }

    public function show(Request $request, Activity $activity): View
    {
        $this->authorize('play', $activity);

        $activeStudentId = (int) $request->session()->get('active_student_id');

        return view('student.activities.play', [
            'activity' => $activity,
            'student' => $request->user()->students()->findOrFail($activeStudentId),
        ]);
    }

    public function submit(
        SubmitActivityAnswersRequest $request,
        Activity $activity,
        ActivitySubmissionService $submissionService,
    ): JsonResponse {
        $this->authorize('play', $activity);

        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        /** @var list<int> $answers */
        $answers = array_values($request->validated('answers'));

        $result = $submissionService->submit($activity, $student, $answers);

        return response()->json([
            'score' => $result['score'],
            'total_questions' => $result['total_questions'],
            'percentage' => $result['percentage'],
            'xp_earned' => $result['xp_earned'],
            'total_xp' => $student->fresh()->total_xp,
            'feedback' => $result['feedback'],
            'attempt_id' => $result['attempt']->id,
        ]);
    }
}
