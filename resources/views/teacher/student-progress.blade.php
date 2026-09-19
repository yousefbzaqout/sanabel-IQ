<?php

declare(strict_types=1);

/**
 * @var \App\Models\Student $student
 * @var array<string, mixed> $analytics
 */

$overview = $analytics['overview'] ?? [];
$stations = $analytics['stations'] ?? [];
$ai = $analytics['ai_interventions'] ?? ['total_hints' => 0];
$learningMinutes = (int) round(((int) ($overview['total_time_spent_seconds'] ?? 0)) / 60);
?>

<x-teacher-layout :title="'تقرير التقدّم — '.$student->name">
    <x-slot name="header">
        <div class="flex flex-col gap-space-sm sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="font-label-md text-label-md font-bold text-secondary">تقرير التقدّم</p>
                <h1 class="font-headline-md text-headline-md font-extrabold text-on-surface">{{ $student->name }}</h1>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">الصف {{ $student->grade_level }} · نظرة سريعة على الإتقان والمحطات</p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="font-label-md text-label-md font-bold text-secondary hover:underline">
                العودة لصفي
            </a>
        </div>
    </x-slot>

    <div class="space-y-space-lg">
        <section class="grid gap-space-md sm:grid-cols-2 xl:grid-cols-4" aria-label="مؤشرات التقدّم">
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">نسبة الإكمال</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-primary">{{ $overview['completion_rate'] ?? 0 }}%</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">متوسط الإتقان</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-secondary">{{ $overview['average_mastery_score'] ?? 0 }}%</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">وقت التعلّم</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-on-surface">{{ $learningMinutes }} د</p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <p class="font-label-sm text-label-sm text-on-surface-variant">تلميحات AI</p>
                <p class="mt-2 font-headline-md text-headline-md font-extrabold text-on-surface">{{ $ai['total_hints'] ?? 0 }}</p>
            </div>
        </section>

        <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
            <h2 class="font-title-md text-title-md font-bold text-on-surface">محطات الدرس</h2>
            <ul class="mt-space-md space-y-space-sm font-body-sm text-body-sm text-on-surface-variant">
                <li class="flex items-center justify-between gap-space-sm border-b border-surface-container-low pb-space-sm">
                    <span>محاولات الصوت</span>
                    <strong class="text-on-surface">{{ $stations['voice']['attempts'] ?? 0 }}</strong>
                </li>
                <li class="flex items-center justify-between gap-space-sm border-b border-surface-container-low pb-space-sm">
                    <span>دقة التتبّع</span>
                    <strong class="text-on-surface">{{ $stations['tracing']['stroke_accuracy'] ?? 0 }}%</strong>
                </li>
                <li class="flex items-center justify-between gap-space-sm">
                    <span>نجاح الاختبار من أول محاولة</span>
                    <strong class="text-on-surface">{{ $stations['quiz_discovery']['first_attempt_success_rate'] ?? 0 }}%</strong>
                </li>
            </ul>
        </section>
    </div>
</x-teacher-layout>
