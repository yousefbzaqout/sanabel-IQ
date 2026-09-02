<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MaterialStatus;
use App\Enums\ParentGoalStatus;
use App\Http\Requests\StoreParentMaterialRequest;
use App\Jobs\ProcessPDFMaterialJob;
use App\Models\Activity;
use App\Models\ParentLearningGoal;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Goals\ParentGoalEvaluatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParentMaterialController extends Controller
{
    public function index(Request $request, ParentGoalEvaluatorService $goalEvaluator): View
    {
        $this->authorize('viewAny', ParentMaterial::class);

        $activeStudentId = (int) $request->session()->get('active_student_id');

        $materials = $request->user()
            ->parentMaterials()
            ->where('student_id', $activeStudentId)
            ->latest()
            ->get();

        $activities = Activity::query()
            ->with('parentMaterial')
            ->where('student_id', $activeStudentId)
            ->whereHas('parentMaterial', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        $activeStudent = Student::query()->find($activeStudentId);

        $activeGoals = $activeStudent === null
            ? collect()
            : ParentLearningGoal::query()
                ->where('parent_id', $request->user()->id)
                ->where('student_id', $activeStudent->id)
                ->where('status', ParentGoalStatus::Pending)
                ->with('subject')
                ->latest()
                ->get()
                ->map(function (ParentLearningGoal $goal) use ($goalEvaluator, $activeStudent): array {
                    $progress = $goalEvaluator->progressForGoal($activeStudent, $goal);

                    return [
                        'goal' => $goal,
                        'activities_completed' => $progress['activities_completed'],
                        'xp_earned' => $progress['xp_earned'],
                        'activity_progress_percent' => $goal->target_activity_count > 0
                            ? min(100, (int) round(($progress['activities_completed'] / $goal->target_activity_count) * 100))
                            : 0,
                        'xp_progress_percent' => $goal->target_xp > 0
                            ? min(100, (int) round(($progress['xp_earned'] / $goal->target_xp) * 100))
                            : 100,
                    ];
                });

        return view('dashboard', [
            'materials' => $materials,
            'activities' => $activities,
            'activeGoals' => $activeGoals,
            'subjects' => Subject::query()->orderBy('name')->get(),
        ]);
    }

    public function show(ParentMaterial $parentMaterial): View
    {
        $this->authorize('view', $parentMaterial);

        return view('materials.show', [
            'material' => $parentMaterial,
        ]);
    }

    public function store(StoreParentMaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', ParentMaterial::class);

        $activeStudentId = (int) $request->session()->get('active_student_id');
        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $uploadedFile = $request->file('file');
        $hashedFilename = hash('sha256', $uploadedFile->getClientOriginalName().microtime(true)).'.pdf';
        $filePath = $uploadedFile->storeAs('', $hashedFilename, 'materials');

        $material = $request->user()->parentMaterials()->create([
            'student_id' => $student->id,
            'title' => $request->validated('title'),
            'file_path' => $filePath,
            'type' => $request->validated('type'),
            'status' => MaterialStatus::Pending,
        ]);

        ProcessPDFMaterialJob::dispatch($material);

        return redirect()
            ->route('dashboard')
            ->with('status', __('Material uploaded successfully.'));
    }

    public function destroy(Request $request, ParentMaterial $parentMaterial): RedirectResponse
    {
        $this->authorize('delete', $parentMaterial);

        Storage::disk('materials')->delete($parentMaterial->file_path);
        $parentMaterial->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', __('Material deleted.'));
    }
}
