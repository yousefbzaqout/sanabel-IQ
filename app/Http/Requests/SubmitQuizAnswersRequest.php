<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitQuizAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'distinct', 'exists:questions,id'],
            'answers.*.selected_option_id' => ['required', 'integer', 'exists:question_options,id'],
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

            $expectedQuestionIds = Question::query()
                ->where('learning_material_id', $learningMaterial->id)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            /** @var list<int> $providedQuestionIds */
            $providedQuestionIds = collect($this->input('answers', []))
                ->pluck('question_id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($expectedQuestionIds !== $providedQuestionIds) {
                $validator->errors()->add(
                    'answers',
                    __('Quiz submission must include an answer for every question exactly once.'),
                );

                return;
            }

            foreach ($this->input('answers', []) as $index => $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $questionId = (int) ($answer['question_id'] ?? 0);
                $selectedOptionId = (int) ($answer['selected_option_id'] ?? 0);

                $optionBelongsToQuestion = QuestionOption::query()
                    ->whereKey($selectedOptionId)
                    ->where('question_id', $questionId)
                    ->exists();

                if (! $optionBelongsToQuestion) {
                    $validator->errors()->add(
                        "answers.{$index}.selected_option_id",
                        __('The selected option does not belong to this question.'),
                    );
                }

                $questionBelongsToMaterial = Question::query()
                    ->whereKey($questionId)
                    ->where('learning_material_id', $learningMaterial->id)
                    ->exists();

                if (! $questionBelongsToMaterial) {
                    $validator->errors()->add(
                        "answers.{$index}.question_id",
                        __('The question does not belong to this learning material.'),
                    );
                }
            }
        });
    }
}
