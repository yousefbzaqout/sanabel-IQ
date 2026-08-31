<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
}
