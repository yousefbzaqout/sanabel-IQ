<?php

declare(strict_types=1);

/**
 * @var \App\Models\User $teacher
 * @var \Illuminate\Support\Collection<int, \App\Models\InteractiveLesson> $lessons
 * @var \Illuminate\Support\Collection<int, \App\Models\Student> $students
 * @var \Illuminate\Support\Collection<int, \App\Models\TeacherLessonAssignment> $assignments
 */
?>

<x-teacher-layout title="تعيين الدروس — بوابة المعلم">
    <x-slot name="header">
        <div>
            <p class="font-label-md text-label-md font-bold text-secondary">أدوات الصف</p>
            <h1 class="font-headline-md text-headline-md font-extrabold text-on-surface">تعيين الدروس</h1>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">اختر درساً من الكتالوج وعيّنه لكامل الصف أو لطالب محدد.</p>
        </div>
    </x-slot>

    <div class="space-y-space-lg">
        @if (session('status'))
            <div class="rounded-xl bg-secondary-fixed/40 px-space-md py-space-sm font-label-md text-label-md font-bold text-on-secondary-fixed">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm sm:p-space-lg">
            <h2 class="font-title-md text-title-md font-bold text-on-surface">تعيين درس جديد</h2>

            <form method="POST" action="{{ route('teacher.lessons.assign') }}" class="mt-space-md grid gap-space-md sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div>
                    <label for="interactive_lesson_id" class="font-label-sm text-label-sm font-bold text-on-surface">الدرس</label>
                    <select
                        id="interactive_lesson_id"
                        name="interactive_lesson_id"
                        required
                        class="mt-1 w-full rounded-lg bg-surface-container-low px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-4 focus:ring-secondary/15"
                    >
                        <option value="">-- اختر الدرس --</option>
                        @foreach ($lessons as $lesson)
                            <option value="{{ $lesson->id }}">
                                [{{ $lesson->subject_code }}] {{ $lesson->title }} (الصف {{ $lesson->grade_level }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="student_id" class="font-label-sm text-label-sm font-bold text-on-surface">الهدف</label>
                    <select
                        id="student_id"
                        name="student_id"
                        class="mt-1 w-full rounded-lg bg-surface-container-low px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-4 focus:ring-secondary/15"
                    >
                        <option value="">كامل الصف (جميع الطلاب)</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">
                                طالب: {{ $student->name }} (الصف {{ $student->grade_level }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="due_date" class="font-label-sm text-label-sm font-bold text-on-surface">تاريخ التسليم</label>
                    <input
                        type="date"
                        id="due_date"
                        name="due_date"
                        class="mt-1 w-full rounded-lg bg-surface-container-low px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-4 focus:ring-secondary/15"
                    >
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-full bg-secondary px-space-md py-2.5 font-label-md text-label-md font-bold text-on-secondary"
                    >
                        تعيين الدرس
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
            <div class="border-b border-surface-container-low px-space-md py-space-sm">
                <h2 class="font-title-md text-title-md font-bold text-on-surface">
                    التعيينات الحالية ({{ $assignments->count() }})
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-right">
                    <thead class="bg-surface-container">
                        <tr class="font-label-sm text-label-sm text-on-surface-variant">
                            <th class="px-space-md py-space-sm font-bold">الدرس</th>
                            <th class="px-space-md py-space-sm font-bold">المادة</th>
                            <th class="px-space-md py-space-sm font-bold">المستهدف</th>
                            <th class="px-space-md py-space-sm font-bold">تاريخ التعيين</th>
                            <th class="px-space-md py-space-sm font-bold">التسليم</th>
                            <th class="px-space-md py-space-sm font-bold">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr class="border-t border-surface-container-low">
                                <td class="px-space-md py-space-sm font-label-md text-label-md font-bold text-on-surface">
                                    {{ $assignment->interactiveLesson?->title ?? '—' }}
                                </td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $assignment->interactiveLesson?->subject_code ?? '—' }}
                                </td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">
                                    @if ($assignment->isClassroomLevel())
                                        كامل الصف
                                    @else
                                        {{ $assignment->student?->name ?? 'طالب' }}
                                    @endif
                                </td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $assignment->assigned_at?->format('Y-m-d') ?? '—' }}
                                </td>
                                <td class="px-space-md py-space-sm font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $assignment->due_date?->format('Y-m-d') ?? 'غير محدد' }}
                                </td>
                                <td class="px-space-md py-space-sm">
                                    <form method="POST" action="{{ route('teacher.lessons.unassign') }}">
                                        @csrf
                                        <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                                        <button
                                            type="submit"
                                            class="font-label-sm text-label-sm font-bold text-error hover:underline"
                                            onclick="return confirm('هل أنت متأكد من إلغاء تعيين هذا الدرس؟')"
                                        >
                                            إلغاء التعيين
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-space-md py-space-xl text-center font-body-sm text-body-sm text-on-surface-variant">
                                    لا توجد تعيينات نشطة حالياً. اختر درساً من الأعلى لبدء التعيين.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-teacher-layout>
