@php
    $teacher = auth()->user();
@endphp

<header class="sticky top-0 z-40 border-b border-surface-container-low bg-surface-container-lowest/95 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-space-md px-gutter">
        <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-space-sm no-underline">
            <img src="{{ asset('brand/logo-official.svg') }}" alt="سنابل IQ" class="h-8 w-auto object-contain">
            <div class="flex flex-col leading-tight">
                <span class="font-headline-sm text-headline-sm font-extrabold text-on-surface">سنابل IQ</span>
                <span class="font-label-sm text-label-sm font-bold text-secondary">بوابة المعلم</span>
            </div>
        </a>

        <nav class="hidden items-center gap-1 rounded-full bg-surface-container p-1 md:flex" aria-label="تنقل المعلم">
            <a
                href="{{ route('teacher.dashboard') }}"
                @class([
                    'rounded-full px-space-md py-space-xs font-label-md text-label-md font-bold transition-colors',
                    'bg-surface-container-lowest text-on-surface shadow-sm' => request()->routeIs('teacher.dashboard', 'teacher.students.*'),
                    'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('teacher.dashboard', 'teacher.students.*'),
                ])
            >صفي</a>
            <a
                href="{{ route('teacher.lessons.index') }}"
                @class([
                    'rounded-full px-space-md py-space-xs font-label-md text-label-md font-bold transition-colors',
                    'bg-surface-container-lowest text-on-surface shadow-sm' => request()->routeIs('teacher.lessons.*'),
                    'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('teacher.lessons.*'),
                ])
            >تعيين الدروس</a>
        </nav>

        <div class="flex items-center gap-space-sm">
            <div class="hidden text-left sm:block sm:text-right">
                <p class="font-label-md text-label-md font-extrabold text-on-surface">{{ $teacher?->name }}</p>
                <p class="font-label-sm text-label-sm text-on-surface-variant">معلّم الصف</p>
            </div>
            <form method="POST" action="{{ route('teacher.logout') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center gap-1 rounded-full bg-surface-container px-space-sm py-space-xs font-label-sm text-label-sm font-bold text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface"
                >
                    <span class="material-symbols-outlined text-base">logout</span>
                    خروج
                </button>
            </form>
        </div>
    </div>

    <nav class="flex items-center justify-center gap-1 border-t border-surface-container-low bg-surface-container-lowest px-gutter py-space-xs md:hidden" aria-label="تنقل المعلم للجوال">
        <a
            href="{{ route('teacher.dashboard') }}"
            @class([
                'flex-1 rounded-full py-space-xs text-center font-label-sm text-label-sm font-bold',
                'bg-secondary text-on-secondary' => request()->routeIs('teacher.dashboard', 'teacher.students.*'),
                'text-on-surface-variant' => ! request()->routeIs('teacher.dashboard', 'teacher.students.*'),
            ])
        >صفي</a>
        <a
            href="{{ route('teacher.lessons.index') }}"
            @class([
                'flex-1 rounded-full py-space-xs text-center font-label-sm text-label-sm font-bold',
                'bg-secondary text-on-secondary' => request()->routeIs('teacher.lessons.*'),
                'text-on-surface-variant' => ! request()->routeIs('teacher.lessons.*'),
            ])
        >تعيين الدروس</a>
    </nav>
</header>
