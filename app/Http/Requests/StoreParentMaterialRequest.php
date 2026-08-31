<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $activeStudentId = (int) $this->session()->get('active_student_id');

        return Student::query()
            ->where('user_id', $this->user()->id)
            ->whereKey($activeStudentId)
            ->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['exam', 'summary', 'worksheet'])],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->redirector->getUrlGenerator()->route('dashboard');
    }
}
