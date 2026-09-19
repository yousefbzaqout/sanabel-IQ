@php
    $student = auth()->user()?->students()->find(session('active_student_id'));
    $xp = (int) ($student?->total_xp ?? 0);
    $streak = (int) ($student?->streak?->current_streak ?? 0);
    $level = max(1, (int) floor($xp / 100) + 1);
@endphp

<header class="fixed top-0 inset-x-0 z-50 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06)]">
    <div class="h-20 w-full px-gutter max-w-7xl mx-auto flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-sm">
            <a href="{{ route('student.dashboard') }}" class="flex items-center gap-space-sm">
                <img alt="سنابل IQ" class="h-10 w-auto object-contain" src="{{ asset('brand/logo-official.svg') }}">
                <div class="flex flex-col">
                    <span class="font-headline-sm text-headline-sm text-on-surface leading-tight font-extrabold">سنابل IQ</span>
                    <span class="px-space-sm py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-label-sm font-bold">بوابة الأبطال الصغار ✨</span>
                </div>
            </a>
        </div>

        <div class="hidden lg:flex items-center gap-space-md">
            <div class="flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-primary-fixed/60 text-on-primary-fixed-variant shadow-sm">
                <span class="material-symbols-outlined text-primary-container text-xl" style="font-variation-settings: 'FILL' 1;">hotel_class</span>
                <span class="font-label-lg text-label-lg font-bold">⭐ {{ number_format($xp) }} XP</span>
            </div>
            <div class="flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-high text-on-surface shadow-sm">
                <span class="material-symbols-outlined text-primary text-xl">local_fire_department</span>
                <span class="font-label-lg text-label-lg font-bold">🔥 {{ $streak }} أيام</span>
            </div>
            <div class="flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-secondary-container/40 text-on-secondary-container shadow-sm">
                <span class="material-symbols-outlined text-secondary text-xl">military_tech</span>
                <span class="font-label-lg text-label-lg font-bold">المستوى {{ $level }}</span>
            </div>
        </div>

        <div class="flex items-center gap-space-sm">
            <x-student.audio-toggle />
            <a href="{{ route('student.adults-portal') }}" class="hidden md:inline-flex items-center gap-space-xs px-space-sm py-space-xs rounded-full bg-surface-container text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface text-label-sm font-bold transition-colors">
                <span class="material-symbols-outlined text-base">family_restroom</span>
                <span>بوابة الكبار</span>
            </a>
            <div class="flex items-center gap-space-xs p-1 pr-3 pl-1 rounded-full bg-surface-container-high">
                <span class="font-label-md text-label-md text-on-surface font-extrabold hidden sm:inline">{{ $student?->name ?? Auth::user()->name }}</span>
                <img alt="Profile" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary-container" src="{{ asset('brand/mascot-sanbal.jpg') }}">
            </div>
        </div>
    </div>

    <nav class="w-full px-gutter bg-surface-container-lowest/80 border-t border-surface-container-low hidden md:flex items-center justify-center gap-space-md py-space-xs" aria-label="تنقل الطالب">
        <a href="{{ route('student.dashboard') }}" @class([
            'px-space-lg py-space-sm rounded-full transition-all font-bold',
            'bg-primary-container text-on-primary-container font-extrabold shadow-[0_6px_16px_-2px_rgba(245,158,11,0.35)]' => request()->routeIs('student.dashboard'),
            'text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! request()->routeIs('student.dashboard'),
        ])>خريطة المغامرة</a>
        <a href="{{ route('student.activities.index') }}" @class([
            'px-space-lg py-space-sm rounded-full transition-all font-bold',
            'bg-primary-container text-on-primary-container font-extrabold shadow-[0_6px_16px_-2px_rgba(245,158,11,0.35)]' => request()->routeIs('student.activities.*'),
            'text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! request()->routeIs('student.activities.*'),
        ])>الأنشطة اليومية</a>
        <a href="{{ route('student.progress') }}" @class([
            'px-space-lg py-space-sm rounded-full transition-all font-bold',
            'bg-primary-container text-on-primary-container font-extrabold shadow-[0_6px_16px_-2px_rgba(245,158,11,0.35)]' => request()->routeIs('student.progress'),
            'text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! request()->routeIs('student.progress'),
        ])>مسار تقدمي</a>
        <a href="{{ route('student.badges') }}" @class([
            'px-space-lg py-space-sm rounded-full transition-all font-bold',
            'bg-primary-container text-on-primary-container font-extrabold shadow-[0_6px_16px_-2px_rgba(245,158,11,0.35)]' => request()->routeIs('student.badges'),
            'text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! request()->routeIs('student.badges'),
        ])>خزانة الجوائز</a>
    </nav>
</header>
