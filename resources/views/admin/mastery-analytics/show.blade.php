<?php

declare(strict_types=1);

/** @var array<string, mixed> $analytics */

$overview = $analytics['overview'];
$stations = $analytics['stations'];
$ai = $analytics['ai_interventions'];
?>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            لوحة إتقان الدروس
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">الطلاب المتابعون</p>
                    <p class="mt-2 text-3xl font-bold text-slate-700 dark:text-slate-200">{{ $overview['students_tracked'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">نسبة إكمال الدروس</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $overview['completion_rate'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">متوسط درجة الإتقان</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $overview['average_mastery_score'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-sm text-gray-500">تدخلات سنبل</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600">{{ $ai['total_hints'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">محطة الصوت</h3>
                    <p class="mt-3 text-sm">درجة النطق: {{ $stations['voice']['pronunciation_score'] }}%</p>
                    <p class="mt-1 text-sm">المحاولات: {{ $stations['voice']['attempts'] }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">محطة التتبع</h3>
                    <p class="mt-3 text-sm">دقة الخط: {{ $stations['tracing']['stroke_accuracy'] }}%</p>
                    <p class="mt-1 text-sm">دقة المسار: {{ $stations['tracing']['path_precision'] }}%</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold">الاكتشاف والاختبار</h3>
                    <p class="mt-3 text-sm">نجاح المحاولة الأولى: {{ $stations['quiz_discovery']['first_attempt_success_rate'] }}%</p>
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
                        <li class="text-gray-500">لا توجد تدخلات مسجّلة.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
