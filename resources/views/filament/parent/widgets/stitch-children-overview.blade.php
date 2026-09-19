@php
    use App\Filament\Parent\Resources\Children\ChildResource;
@endphp

<x-filament-widgets::widget>
    <div class="flex flex-col gap-space-lg font-body-md text-on-surface" dir="rtl">
        <div class="flex flex-col gap-space-md md:flex-row md:items-end md:justify-between">
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant font-bold">لوحة التحكم ← الأبناء</p>
                <h2 class="mt-1 font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">إدارة الأبناء</h2>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">متابعة الحسابات التعليمية وتفعيل دخول كل طفل برمز PIN</p>
                @if (! empty($familyCode))
                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-secondary-container px-4 py-2">
                        <span class="font-label-sm text-label-sm text-on-secondary-container">رمز العائلة للدخول:</span>
                        <span class="font-headline-sm text-headline-sm font-extrabold text-on-secondary-container tracking-widest" dir="ltr">{{ $familyCode }}</span>
                    </div>
                @endif
            </div>
            <a
                href="{{ ChildResource::getUrl('create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-primary-container px-6 py-3 font-label-lg text-label-lg font-bold text-on-primary-container shadow-[0_4px_0_#d97706] hover:translate-y-0.5 hover:shadow-[0_2px_0_#d97706] transition-all"
            >
                <span class="material-symbols-outlined text-xl">add_circle</span>
                إضافة طفل جديد
            </a>
        </div>

        <div class="grid grid-cols-1 gap-space-md md:grid-cols-3">
            <div class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-space-lg shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">إجمالي الأبناء المسجلين</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="font-headline-lg text-headline-lg text-on-surface font-extrabold">{{ $children->count() }}</span>
                            <span class="font-label-md text-label-md font-bold text-secondary">أطفال مفعّلين</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-fixed text-secondary">
                        <span class="material-symbols-outlined text-2xl">family_restroom</span>
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-space-lg shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">إجمالي النقاط المشتركة</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="font-headline-lg text-headline-lg text-primary font-extrabold">{{ number_format($totalXp) }}</span>
                            <span class="font-label-md text-label-md font-semibold text-on-surface-variant">XP مجمّعة</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-fixed text-primary">
                        <span class="material-symbols-outlined text-2xl">stars</span>
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-space-lg shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">حالة الربط المدرسي</span>
                        <p class="mt-1 font-headline-sm text-headline-sm text-on-surface font-bold">{{ $schoolName }}</p>
                        <p class="font-label-sm text-label-sm font-semibold text-secondary">مزامنة سحابية مستمرة</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-tertiary-fixed text-tertiary">
                        <span class="material-symbols-outlined text-2xl">hub</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-space-sm">
            <span class="h-6 w-2.5 rounded-full bg-secondary"></span>
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">الأطفال المسجلون والملفات النشطة</h3>
        </div>

        <div class="grid grid-cols-1 gap-space-lg lg:grid-cols-3">
            @foreach ($children as $child)
                @php
                    $isActive = $active && (int) $active->id === (int) $child->id;
                    $streak = (int) ($child->streak?->current_streak ?? 0);
                    $level = max(1, (int) floor($child->total_xp / 100) + 1);
                @endphp
                <div class="relative flex flex-col justify-between rounded-lg bg-surface-container-lowest p-space-lg shadow-sm {{ $isActive ? 'ring-2 ring-primary-container/40' : '' }}">
                    @if ($isActive)
                        <div class="absolute inset-x-0 top-0 h-1.5 rounded-t-lg bg-gradient-to-l from-primary-container to-secondary"></div>
                    @endif
                    <div>
                        <div class="flex items-start justify-between gap-space-sm">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary-fixed text-2xl font-bold text-on-primary-fixed-variant shadow-md">
                                {{ mb_substr($child->name, 0, 1) }}
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                @if ($isActive)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 font-label-sm text-label-sm font-bold text-on-secondary-container">
                                        <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span>
                                        الحساب النشط حالياً
                                    </span>
                                @else
                                    <span class="rounded-full bg-surface-container px-2 py-0.5 font-label-sm text-label-sm font-semibold text-on-surface-variant">حساب مسجّل</span>
                                @endif
                                <span class="rounded-full bg-tertiary-fixed px-2 py-0.5 font-label-sm text-label-sm font-semibold text-on-tertiary-container">الصف {{ $child->grade_level }}</span>
                                @if ($child->hasChildLoginEnabled())
                                    <span class="rounded-full bg-secondary-container px-2 py-0.5 font-label-sm text-label-sm font-bold text-on-secondary-container">دخول مفعّل</span>
                                @else
                                    <span class="rounded-full bg-surface-container px-2 py-0.5 font-label-sm text-label-sm font-semibold text-on-surface-variant">دخول غير مفعّل</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-space-md">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-extrabold">{{ $child->name }}</h3>
                            <p class="mt-0.5 font-label-md text-label-md font-bold {{ $isActive ? 'text-secondary' : 'text-primary' }}">الصف {{ $child->grade_level }}</p>
                        </div>
                        <div class="mt-space-md grid grid-cols-2 gap-2">
                            <div class="flex items-center gap-2 rounded-xl bg-primary-fixed/50 p-3">
                                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">star</span>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-primary-fixed-variant">المستوى {{ $level }}</p>
                                    <p class="font-label-lg text-label-lg font-extrabold text-on-primary-fixed">{{ number_format($child->total_xp) }} XP</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 rounded-xl bg-error-container/60 p-3">
                                <span class="material-symbols-outlined text-error" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-error-container">سلسلة الالتزام</p>
                                    <p class="font-label-lg text-label-lg font-extrabold text-error">{{ $streak }} يوماً</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-space-lg flex flex-col gap-2">
                        @if ($isActive)
                            <button type="button" disabled class="flex w-full cursor-default items-center justify-center gap-2 rounded-full bg-secondary-container py-2.5 font-label-lg text-label-lg font-bold text-on-secondary-container">
                                <span class="material-symbols-outlined text-lg">verified_user</span>
                                الحساب النشط حالياً
                            </button>
                        @else
                            <form method="POST" action="{{ route('students.select', $child) }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-full bg-tertiary-fixed py-2.5 font-label-lg text-label-lg font-bold text-on-tertiary-container shadow-sm hover:bg-secondary hover:text-on-secondary transition-colors">
                                    <span class="material-symbols-outlined text-lg">sync_alt</span>
                                    تبديل إلى هذا الحساب
                                </button>
                            </form>
                        @endif
                        <a href="{{ ChildResource::getUrl('edit', ['record' => $child]) }}" class="inline-flex items-center justify-center gap-1.5 rounded-full bg-surface-container-low py-2 font-label-md text-label-md font-semibold text-on-surface hover:bg-surface-container">
                            <span class="material-symbols-outlined text-base">pin</span>
                            {{ $child->hasChildLoginEnabled() ? 'إعادة تعيين PIN' : 'تعيين رمز PIN' }}
                        </a>
                    </div>
                </div>
            @endforeach

            <a href="{{ ChildResource::getUrl('create') }}" class="group flex min-h-[320px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-outline-variant/40 bg-surface-container-lowest/60 p-space-lg text-center shadow-sm transition hover:bg-surface-container-lowest hover:border-primary-container">
                <div class="mb-space-md flex h-14 w-14 items-center justify-center rounded-full bg-primary-container text-on-primary-container shadow-md group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-3xl">person_add</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-extrabold">إضافة مقعد لطفل آخر</h3>
                <p class="mt-2 max-w-xs font-body-sm text-body-sm text-on-surface-variant">فعّل الحساب التفاعلي مع مدرسة طفلك وتابع إنجازاته في سنابل IQ.</p>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
