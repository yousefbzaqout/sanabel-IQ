<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
        $subjectId = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')
                    ->where(fn ($query) => $query->where('grade_level', (int) $this->input('grade_level')))
                    ->ignore($subjectId),
            ],
            'grade_level' => ['required', 'integer', 'between:1,5'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
