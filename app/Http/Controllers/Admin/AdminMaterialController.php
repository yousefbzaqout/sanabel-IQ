<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMaterialsRequest;
use App\Http\Requests\Admin\StoreMaterialRequest;
use App\Models\LearningMaterial;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminMaterialController extends Controller
{
    public function store(StoreMaterialRequest $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validated();
        $nextOrder = ((int) $subject->learningMaterials()->max('order_column')) + 1;

        $subject->learningMaterials()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'xp_reward' => (int) $validated['xp_reward'],
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'order_column' => max(1, $nextOrder),
        ]);

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('status', __('Learning material created successfully.'));
    }

    public function update(StoreMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $validated = $request->validated();

        $learningMaterial->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'xp_reward' => (int) $validated['xp_reward'],
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]);

        return redirect()
            ->route('admin.subjects.show', $learningMaterial->subject_id)
            ->with('status', __('Learning material updated successfully.'));
    }

    public function destroy(LearningMaterial $learningMaterial): RedirectResponse
    {
        $subjectId = $learningMaterial->subject_id;
        $learningMaterial->delete();

        return redirect()
            ->route('admin.subjects.show', $subjectId)
            ->with('status', __('Learning material deleted successfully.'));
    }

    public function reorder(ReorderMaterialsRequest $request): RedirectResponse
    {
        $orderedIds = array_values($request->validated('ordered_ids'));

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $materialId) {
                LearningMaterial::query()
                    ->whereKey($materialId)
                    ->update(['order_column' => $index + 1]);
            }
        });

        $subjectId = LearningMaterial::query()->whereKey($orderedIds[0])->value('subject_id');

        return redirect()
            ->route('admin.subjects.show', $subjectId)
            ->with('status', __('Materials reordered successfully.'));
    }
}
