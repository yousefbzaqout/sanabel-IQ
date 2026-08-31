<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\LearningMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderMaterialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'distinct', 'exists:learning_materials,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var list<int> $orderedIds */
            $orderedIds = array_values(array_map('intval', $this->input('ordered_ids', [])));

            $materials = LearningMaterial::query()
                ->whereIn('id', $orderedIds)
                ->get(['id', 'subject_id', 'order_column']);

            $subjectIds = $materials->pluck('subject_id')->unique()->values();

            if ($subjectIds->count() !== 1) {
                $validator->errors()->add(
                    'ordered_ids',
                    __('All materials in a reorder payload must belong to the same subject.'),
                );

                return;
            }

            $subjectId = (int) $subjectIds->first();
            $expectedIds = LearningMaterial::query()
                ->where('subject_id', $subjectId)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            $providedIds = collect($orderedIds)->sort()->values()->all();

            if ($expectedIds !== $providedIds) {
                $validator->errors()->add(
                    'ordered_ids',
                    __('Reorder payload must include every learning material for the subject exactly once.'),
                );
            }
        });
    }
}
