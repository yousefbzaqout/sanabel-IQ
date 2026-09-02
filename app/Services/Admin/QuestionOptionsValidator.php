<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\QuestionType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class QuestionOptionsValidator
{
    /**
     * @param  list<array{option_text?: string, is_correct?: bool}>  $options
     */
    public static function validate(QuestionType $type, array $options): void
    {
        $validator = Validator::make(
            ['options' => $options],
            [
                'options' => ['required', 'array', 'min:1'],
                'options.*.option_text' => ['required', 'string', 'max:1000'],
                'options.*.is_correct' => ['required', 'boolean'],
            ],
        );

        $validator->after(function ($validator) use ($type, $options): void {
            if ($validator->errors()->isNotEmpty()) {
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

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
