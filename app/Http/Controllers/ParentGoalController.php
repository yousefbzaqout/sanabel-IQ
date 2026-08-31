<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ParentGoalStatus;
use App\Http\Requests\StoreParentLearningGoalRequest;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentGoalController extends Controller
{
    public function index(Request $request): View
    {
        $goals = ParentLearningGoal::query()
            ->where('parent_id', $request->user()->id)
            ->with(['student', 'subject'])
            ->latest()
            ->paginate(15);

        return view('parent.goals.index', [
            'goals' => $goals,
        ]);
    }

    public function store(StoreParentLearningGoalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Student $student */
        $student = Student::query()->findOrFail($validated['student_id']);
        $this->authorize('update', $student);

        ParentLearningGoal::query()->create([
            'parent_id' => $request->user()->id,
            'student_id' => $student->id,
            'subject_id' => $validated['subject_id'] ?? null,
            'target_activity_count' => (int) $validated['target_activity_count'],
            'target_xp' => (int) ($validated['target_xp'] ?? 0),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => ParentGoalStatus::Pending,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', __('Learning goal created successfully.'));
    }
}
