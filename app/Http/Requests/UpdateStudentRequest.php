<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:6'],
            'school_term' => ['required', 'integer', 'in:1,2'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
