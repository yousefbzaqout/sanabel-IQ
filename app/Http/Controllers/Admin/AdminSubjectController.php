<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $gradeLevel = (int) $request->integer('grade', 1);
        $gradeLevel = max(1, min(5, $gradeLevel));

        $subjects = Subject::query()
            ->where('grade_level', $gradeLevel)
            ->withCount('learningMaterials')
            ->orderBy('name')
            ->get();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $gradeLevel = (int) $validated['grade_level'];

        Subject::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['code']).'-'.$gradeLevel,
        ]);

        return redirect()
            ->route('admin.subjects.index', ['grade' => $gradeLevel])
            ->with('status', __('Subject created successfully.'));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validated();
        $gradeLevel = (int) $validated['grade_level'];

        $subject->update([
            ...$validated,
            'slug' => Str::slug($validated['code']).'-'.$gradeLevel,
        ]);

        return redirect()
            ->route('admin.subjects.index', ['grade' => $gradeLevel])
            ->with('status', __('Subject updated successfully.'));
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $gradeLevel = $subject->grade_level ?? 1;
        $subject->delete();

        return redirect()
            ->route('admin.subjects.index', ['grade' => $gradeLevel])
            ->with('status', __('Subject deleted successfully.'));
    }

    public function show(Subject $subject): View
    {
        $subject->load(['learningMaterials' => fn ($query) => $query->orderBy('order_column')]);

        return view('admin.subjects.show', [
            'subject' => $subject,
        ]);
    }
}
