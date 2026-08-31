<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitActivityAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Activity|null $activity */
            $activity = $this->route('activity');

            if (! $activity instanceof Activity) {
                return;
            }

            $totalQuestions = count($activity->payload['questions'] ?? []);
            $answers = $this->input('answers');

            if (! is_array($answers)) {
                return;
            }

            if (count($answers) !== $totalQuestions) {
                $validator->errors()->add(
                    'answers',
                    __('All questions must be answered before submitting.'),
                );
            }

            foreach ($answers as $index => $answer) {
                if (! is_numeric($answer)) {
                    continue;
                }

                $optionsCount = count($activity->payload['questions'][$index]['options'] ?? []);

                if ((int) $answer >= $optionsCount) {
                    $validator->errors()->add(
                        "answers.{$index}",
                        __('The selected answer is out of range.'),
                    );
                }
            }
        });
    }
}
