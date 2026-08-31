<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
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
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'integer', 'between:1,100'],
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_text' => ['required', 'string', 'max:1000'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $options = $this->input('options', []);

        if (! is_array($options)) {
            return;
        }

        $normalized = [];

        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $normalized[] = [
                'option_text' => $option['option_text'] ?? '',
                'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $this->merge(['options' => $normalized]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = QuestionType::tryFrom((string) $this->input('type'));
            $options = $this->input('options', []);

            if (! is_array($options)) {
                return;
            }

            $correctCount = collect($options)
                ->filter(fn (array $option): bool => (bool) ($option['is_correct'] ?? false))
                ->count();

            if (in_array($type, [QuestionType::Mcq, QuestionType::TrueFalse, QuestionType::FillBlank], true)
                && $correctCount < 1) {
                $validator->errors()->add(
                    'options',
                    __('At least one option must be marked as correct.'),
                );
            }

            if ($type === QuestionType::TrueFalse && count($options) !== 2) {
                $validator->errors()->add(
                    'options',
                    __('True/False questions must have exactly two options.'),
                );
            }
        });
    }
}
