@php
    use App\Filament\Parent\Resources\Materials\MaterialResource;
@endphp

<x-filament-widgets::widget>
    <div class="flex flex-col gap-space-md font-body-md text-on-surface" dir="rtl">
        <div class="flex flex-col gap-space-md md:flex-row md:items-end md:justify-between">
            <div>
                <nav class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant">
                    <span>لوحة التحكم</span>
                    <span class="material-symbols-outlined text-base">chevron_left</span>
                    <span class="font-bold text-on-surface">المواد التعليمية</span>
                </nav>
                <h2 class="mt-2 font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">المواد والدروس التعليمية</h2>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                    @if ($student)
                        استعراض المقررات والمحطات التفاعلية المخصصة لـ {{ $student->name }}
                    @else
                        أضف طفلاً نشطاً لعرض المواد المخصصة
                    @endif
                </p>
            </div>
            <a href="{{ MaterialResource::getUrl('create') }}" class="inline-flex items-center gap-2 rounded-full bg-primary-container px-6 py-3 font-label-lg text-label-lg font-bold text-on-primary-container shadow-[0_4px_0_#d97706] hover:translate-y-0.5 hover:shadow-[0_2px_0_#d97706] transition-all">
                <span class="material-symbols-outlined">rocket_launch</span>
                إضافة مادة جديدة
            </a>
        </div>

        @if ($materialsCount === 0)
            <div class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-space-xl text-center shadow-sm">
                <div class="mb-space-md inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-4 py-1 font-label-sm text-label-sm font-bold text-on-primary-fixed-variant">
                    <span class="material-symbols-outlined text-base">auto_awesome</span>
                    بداية رحلة التعلّم الممتعة
                </div>
                <img alt="سنبل" class="mx-auto mb-space-md h-36 w-36 rounded-full object-cover drop-shadow-xl" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-extrabold">لا توجد مواد دراسية مسجلة حالياً</h3>
                <p class="mx-auto mt-2 max-w-xl font-body-sm text-body-sm text-on-surface-variant">
                    اختر الآن من مكتبة المحطات والأنشطة التفاعلية المناسبة لصف طفلك لتنطلق في رحلة التعلم الذكي.
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
