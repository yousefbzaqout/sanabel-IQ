<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'xp_reward' => ['required', 'integer', 'between:10,500'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_published')) {
            $this->merge([
                'is_published' => filter_var($this->input('is_published'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
