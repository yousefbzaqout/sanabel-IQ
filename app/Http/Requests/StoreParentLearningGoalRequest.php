<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentLearningGoalRequest extends FormRequest
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
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'target_activity_count' => ['required', 'integer', 'min:1', 'max:100'],
            'target_xp' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
