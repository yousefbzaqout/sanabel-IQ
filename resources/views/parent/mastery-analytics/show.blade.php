<?php

declare(strict_types=1);

/** @var array<string, mixed> $analytics */
/** @var \App\Models\Student $student */

$overview = $analytics['overview'];
$stations = $analytics['stations'];
$ai = $analytics['ai_interventions'];
?>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                إتقان الدروس
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $student->name }} — الصف {{ $student->grade_level }}
            </p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">نسبة إكمال الدروس</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $overview['completion_rate'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">متوسط درجة الإتقان</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $overview['average_mastery_score'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">الوقت الكلي</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ $overview['total_time_spent_seconds'] }} ث</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">تدخلات سنبل</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600">{{ $ai['total_hints'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">محطة الصوت</h3>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">درجة النطق: {{ $stations['voice']['pronunciation_score'] }}%</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">عدد المحاولات: {{ $stations['voice']['attempts'] }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">محطة التتبع</h3>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">دقة الخط: {{ $stations['tracing']['stroke_accuracy'] }}%</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">دقة المسار: {{ $stations['tracing']['path_precision'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">الاكتشاف والاختبار</h3>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">نجاح المحاولة الأولى: {{ $stations['quiz_discovery']['first_attempt_success_rate'] }}%</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold">الوقت حسب الدرس</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($analytics['time_by_lesson'] as $lessonKey => $seconds)
                            <li class="flex justify-between gap-4">
                                <span class="font-mono text-xs text-gray-500">{{ $lessonKey }}</span>
                                <span>{{ $seconds }} ث</span>
                            </li>
                        @empty
                            <li class="text-gray-500">لا توجد بيانات بعد.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="mb-3 text-lg font-semibold">الوقت حسب المحطة</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($analytics['time_by_station'] as $station => $seconds)
                            <li class="flex justify-between gap-4">
                                <span>المحطة {{ $station }}</span>
                                <span>{{ $seconds }} ث</span>
                            </li>
                        @empty
                            <li class="text-gray-500">لا توجد بيانات بعد.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <h3 class="mb-3 text-lg font-semibold">تدخلات سنبل حسب المهارة</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($ai['by_concept'] as $concept => $count)
                        <li class="flex justify-between gap-4">
                            <span class="font-mono text-xs">{{ $concept }}</span>
                            <span>{{ $count }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">لم يسجّل سنبل تلميحات بعد.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
