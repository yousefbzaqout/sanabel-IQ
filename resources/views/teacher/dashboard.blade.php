<?php

declare(strict_types=1);

/**
 * @var \App\Models\User $teacher
 * @var \Illuminate\Support\Collection<int, \App\Models\Student> $students
 * @var array<string, mixed> $classroom
 */

$overview = $classroom['overview'] ?? [];
$ai = $classroom['ai_interventions'] ?? ['total_hints' => 0];
?>

<x-teacher-layout title="صفي — بوابة المعلم">
    <x-slot name="header">
        <div class="flex flex-col gap-space-sm sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="font-label-md text-label-md font-bold text-secondary">مرحباً، {{ $teacher->name }}</p>
                <h1 class="font-headline-md text-headline-md font-extrabold text-on-surface">صفي — متابعة الطلاب</h1>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">راجع تقدّم طلاب مدرستك وعيّن دروساً تفاعلية بسرعة.</p>
            </div>
            <a
                href="{{ route('teacher.lessons.index') }}"
                class="inline-flex items-center justify-center gap-1 rounded-full bg-secondary px-space-md py-space-sm font-label-md text-label-md font-bold text-on-secondary"
            >
                <span class="material-symbols-outlined text-base">assignment_add</span>
                تعيين درس
            </a>
        </div>
    </x-slot>

    <div class="space-y-space-lg">
        <section class="grid gap-space-md sm:grid-cols-3" aria-label="ملخص الصف">
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">طلاب المدرسة</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-on-surface">{{ $students->count() }}</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">طلاب لديهم تحليلات</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-secondary">{{ $overview['students_tracked'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">متوسط الإتقان</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-primary">{{ $overview['average_mastery_score'] ?? 0 }}%</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm" aria-label="قائمة طلاب الصف">
            <div class="border-b border-surface-container-low px-space-md py-space-sm">
                <h2 class="font-title-md text-title-md font-bold text-on-surface">طلاب الصف</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-right">
                    <thead class="bg-surface-container">
                        <tr class="font-label-sm text-label-sm text-on-surface-variant">
                            <th class="px-space-md py-space-sm font-bold">الطالب</th>
                            <th class="px-space-md py-space-sm font-bold">الصف</th>
                            <th class="px-space-md py-space-sm font-bold">XP</th>
                            <th class="px-space-md py-space-sm font-bold">التقرير</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr class="border-t border-surface-container-low">
                                <td class="px-space-md py-space-sm font-label-md text-label-md font-bold text-on-surface">{{ $student->name }}</td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">{{ $student->grade_level }}</td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">{{ $student->total_xp }}</td>
                                <td class="px-space-md py-space-sm">
                                    <a
                                        href="{{ route('teacher.students.progress', $student) }}"
                                        class="font-label-sm text-label-sm font-bold text-secondary hover:underline"
                                    >
                                        تقرير التقدّم
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-space-md py-space-xl text-center font-body-sm text-body-sm text-on-surface-variant">
                                    لا يوجد طلاب مرتبطون بهذه المدرسة بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="font-label-sm text-label-sm text-on-surface-variant">
            تلميحات سنبل المسجّلة للصف: {{ $ai['total_hints'] ?? 0 }}
        </p>
    </div>
</x-teacher-layout>
