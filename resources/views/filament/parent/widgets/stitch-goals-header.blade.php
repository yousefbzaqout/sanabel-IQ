@php
    use App\Filament\Parent\Resources\Goals\GoalResource;
@endphp

<x-filament-widgets::widget>
    <div class="flex flex-col gap-space-md font-body-md text-on-surface" dir="rtl">
        <div class="flex flex-col gap-space-md md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant">
                    <span>لوحة التحكم</span>
                    <span class="material-symbols-outlined text-base">chevron_left</span>
                    <span class="font-semibold text-on-surface">الأهداف الأسبوعية</span>
                </div>
                <div class="mt-1 flex flex-wrap items-baseline gap-2">
                    <h2 class="font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">أهداف وتحديات الأسبوع</h2>
                    @if ($total > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-0.5 font-label-sm text-label-sm font-bold text-on-primary-fixed-variant">
                            <span class="h-1.5 w-1.5 animate-ping rounded-full bg-primary-container"></span>
                            {{ $completed }} من {{ $total }} مكتملة
                        </span>
                    @endif
                </div>
                <p class="mt-1 max-w-2xl font-body-sm text-body-sm text-on-surface-variant">
                    @if ($student)
                        متابعة أهداف الإتقان ونقاط الخبرة لـ {{ $student->name }} خلال الأسبوع الحالي.
                    @else
                        اختر طفلاً نشطاً لعرض الأهداف الأسبوعية.
                    @endif
                </p>
            </div>
            <a href="{{ GoalResource::getUrl('create') }}" class="inline-flex items-center gap-2 rounded-full bg-primary-container px-6 py-2.5 font-label-lg text-label-lg font-bold text-on-primary-container shadow-md hover:brightness-105">
                <span class="material-symbols-outlined">add_circle</span>
                تحديد هدف جديد
            </a>
        </div>

        @if ($total > 0)
            <div class="relative overflow-hidden rounded-lg bg-gradient-to-l from-primary-fixed/60 via-surface-container-low to-secondary-fixed/40 p-space-lg shadow-sm">
                <div class="mb-space-sm flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-lowest px-3 py-1 font-label-sm text-label-sm font-bold text-secondary shadow-sm">
                        <span class="material-symbols-outlined text-base">verified</span>
                        مسار إتقان {{ $student?->name }}
                    </span>
                </div>
                <div class="mb-2 flex items-baseline gap-2">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">إنجاز مستمر!</h3>
                    <span class="font-label-md text-label-md font-bold text-primary">تم إنجاز {{ $completed }} من {{ $total }} أهداف</span>
                </div>
                <div class="space-y-1.5">
                    <div class="flex justify-between font-label-sm text-label-sm font-semibold text-on-surface-variant">
                        <span>نسبة إتمام خطة الأسبوع</span>
                        <span class="font-bold text-primary">{{ $pct }}% مكتمل</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-surface-container-high p-0.5 shadow-inner">
                        <div class="h-full rounded-full bg-gradient-to-r from-secondary to-primary-container" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
