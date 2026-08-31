<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\LearningMaterial;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderQuestionsRequest extends FormRequest
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
            'ordered_ids.*' => ['integer', 'distinct', 'exists:questions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var LearningMaterial $learningMaterial */
            $learningMaterial = $this->route('learningMaterial');

            /** @var list<int> $orderedIds */
            $orderedIds = array_values(array_map('intval', $this->input('ordered_ids', [])));

            $expectedIds = Question::query()
                ->where('learning_material_id', $learningMaterial->id)
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
                    __('Reorder payload must include every question for the material exactly once.'),
                );
            }
        });
    }
}
