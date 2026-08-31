<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderQuestionsRequest;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Models\LearningMaterial;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminQuestionController extends Controller
{
    public function index(LearningMaterial $learningMaterial): View
    {
        $learningMaterial->load([
            'subject',
            'questions.options',
        ]);

        return view('admin.questions.index', [
            'material' => $learningMaterial,
        ]);
    }

    public function store(StoreQuestionRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $validated = $request->validated();
        $maxOrder = $learningMaterial->questions()->max('order_column');
        $nextOrder = $maxOrder === null ? 0 : ((int) $maxOrder) + 1;

        DB::transaction(function () use ($learningMaterial, $validated, $nextOrder): void {
            $question = $learningMaterial->questions()->create([
                'type' => $validated['type'],
                'prompt' => $validated['prompt'],
                'explanation' => $validated['explanation'] ?? null,
                'points' => (int) $validated['points'],
                'order_column' => $nextOrder,
            ]);

            $this->syncOptions($question, $validated['options']);
        });

        return redirect()
            ->route('admin.materials.questions.index', $learningMaterial)
            ->with('status', __('Question created successfully.'));
    }

    public function update(StoreQuestionRequest $request, Question $question): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($question, $validated): void {
            $question->update([
                'type' => $validated['type'],
                'prompt' => $validated['prompt'],
                'explanation' => $validated['explanation'] ?? null,
                'points' => (int) $validated['points'],
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $validated['options']);
        });

        return redirect()
            ->route('admin.materials.questions.index', $question->learning_material_id)
            ->with('status', __('Question updated successfully.'));
    }

    public function destroy(Question $question): RedirectResponse
    {
        $materialId = $question->learning_material_id;
        $question->delete();

        return redirect()
            ->route('admin.materials.questions.index', $materialId)
            ->with('status', __('Question deleted successfully.'));
    }

    public function reorder(ReorderQuestionsRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $orderedIds = array_values($request->validated('ordered_ids'));

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $questionId) {
                Question::query()
                    ->whereKey($questionId)
                    ->update(['order_column' => $index]);
            }
        });

        return redirect()
            ->route('admin.materials.questions.index', $learningMaterial)
            ->with('status', __('Questions reordered successfully.'));
    }

    /**
     * @param  list<array{option_text: string, is_correct: bool}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct' => (bool) $option['is_correct'],
                'order_column' => $index,
            ]);
        }
    }
}
