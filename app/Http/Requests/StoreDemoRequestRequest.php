<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DemoRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemoRequestRequest extends FormRequest
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
            'school_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'email' => ['required', 'email:filter', 'max:255'],
            'seat_range' => ['required', 'string', Rule::in(DemoRequest::SEAT_RANGES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_name.required' => 'اسم المدرسة مطلوب.',
            'school_name.max' => 'اسم المدرسة طويل جداً.',
            'contact_name.required' => 'اسم المسؤول مطلوب.',
            'contact_name.max' => 'اسم المسؤول طويل جداً.',
            'job_title.required' => 'المسمى الوظيفي مطلوب.',
            'job_title.max' => 'المسمى الوظيفي طويل جداً.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'phone.regex' => 'رقم الجوال غير صالح. استخدم صيغة مثل 05xxxxxxxx.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'seat_range.required' => 'عدد المقاعد المتوقع مطلوب.',
            'seat_range.in' => 'نطاق المقاعد غير صالح.',
            'notes.max' => 'الملاحظات طويلة جداً.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_name' => 'اسم المدرسة',
            'contact_name' => 'اسم المسؤول',
            'job_title' => 'المسمى الوظيفي',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'seat_range' => 'عدد المقاعد',
            'notes' => 'الملاحظات',
        ];
    }
}
