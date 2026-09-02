@php
    $sizeClasses = match ($size) {
        'sm' => 'w-20 h-20',
        'lg' => 'w-40 h-40',
        default => 'w-28 h-28',
    };

    $motionClass = match ($state) {
        'happy' => 'animate-bounce',
        'thinking' => 'animate-pulse',
        'encouraging' => 'animate-[wiggle_1.2s_ease-in-out_infinite]',
        default => '',
    };
@endphp

<div
    class="relative inline-flex flex-col items-center gap-2"
    data-mascot-name="sonbol"
    data-mascot-state="{{ $state }}"
    x-data="{ bubbleVisible: true }"
    role="group"
    aria-label="سنبل المساعد"
>
    @if ($showBubble && filled($message))
        <div
            x-show="bubbleVisible"
            x-transition
            class="relative max-w-[14rem] rounded-2xl border border-amber-200 bg-white px-3 py-2 text-center text-sm font-medium text-amber-950 shadow-md dark:border-amber-700 dark:bg-amber-950 dark:text-amber-50"
            data-mascot-bubble
            dir="rtl"
        >
            <p>{{ $message }}</p>
            <button
                type="button"
                class="absolute -top-2 -start-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs text-amber-800 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-100"
                @click="bubbleVisible = false; $store.audio?.speak(@js($message))"
                aria-label="سماع الرسالة"
                title="🔊 سماع"
            >
                🔊
            </button>
            <span class="absolute -bottom-2 start-1/2 h-3 w-3 -translate-x-1/2 rotate-45 border-b border-e border-amber-200 bg-white dark:border-amber-700 dark:bg-amber-950"></span>
        </div>
    @endif

    <div class="{{ $sizeClasses }} {{ $motionClass }}" role="img" aria-label="سنبل — {{ $state }}">
        <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" class="h-full w-full drop-shadow-md" aria-hidden="true">
            {{-- Body / wheat-bird hybrid --}}
            <ellipse cx="60" cy="72" rx="28" ry="26" fill="#F59E0B" />
            <ellipse cx="60" cy="74" rx="18" ry="16" fill="#FBBF24" />

            {{-- Wings by state --}}
            @if ($state === 'happy')
                <path d="M32 60 C18 48 16 34 28 30 C36 42 40 52 42 62 Z" fill="#D97706" />
                <path d="M88 60 C102 48 104 34 92 30 C84 42 80 52 78 62 Z" fill="#D97706" />
            @elseif ($state === 'thinking')
                <path d="M34 68 C22 62 20 50 30 48 C36 56 38 62 40 68 Z" fill="#D97706" />
                <path d="M86 68 C98 62 100 50 90 48 C84 56 82 62 80 68 Z" fill="#D97706" />
            @elseif ($state === 'encouraging')
                <path d="M34 70 C24 74 22 62 32 58 C36 64 38 68 40 72 Z" fill="#D97706" />
                <path d="M86 70 C96 74 98 62 88 58 C84 64 82 68 80 72 Z" fill="#D97706" />
            @else
                <path d="M34 70 C24 66 22 54 32 52 C36 60 38 66 40 72 Z" fill="#D97706" />
                <path d="M86 70 C96 66 98 54 88 52 C84 60 82 66 80 72 Z" fill="#D97706" />
            @endif

            {{-- Head --}}
            <circle cx="60" cy="42" r="22" fill="#FBBF24" />
            <circle cx="60" cy="42" r="16" fill="#FDE68A" />

            {{-- Crest / wheat tip --}}
            <ellipse cx="60" cy="18" rx="8" ry="12" fill="#CA8A04" />
            <ellipse cx="52" cy="22" rx="5" ry="8" fill="#D97706" transform="rotate(-20 52 22)" />
            <ellipse cx="68" cy="22" rx="5" ry="8" fill="#D97706" transform="rotate(20 68 22)" />

            {{-- Beak --}}
            <path d="M60 48 L72 52 L60 56 Z" fill="#EA580C" />

            {{-- Eyes by state --}}
            @if ($state === 'happy')
                <path d="M50 40 Q54 36 58 40" stroke="#1F2937" stroke-width="2.5" fill="none" stroke-linecap="round" />
                <path d="M62 40 Q66 36 70 40" stroke="#1F2937" stroke-width="2.5" fill="none" stroke-linecap="round" />
            @elseif ($state === 'thinking')
                <circle cx="52" cy="42" r="3.5" fill="#1F2937" />
                <circle cx="68" cy="42" r="3.5" fill="#1F2937" />
                <path d="M46 34 Q52 30 58 34" stroke="#1F2937" stroke-width="2" fill="none" stroke-linecap="round" />
                <circle cx="82" cy="22" r="3" fill="#94A3B8" opacity="0.7" />
                <circle cx="88" cy="14" r="2" fill="#94A3B8" opacity="0.5" />
                <circle cx="92" cy="8" r="1.5" fill="#94A3B8" opacity="0.4" />
            @elseif ($state === 'encouraging')
                <circle cx="52" cy="42" r="3.5" fill="#1F2937" />
                <circle cx="68" cy="42" r="3.5" fill="#1F2937" />
                <path d="M52 54 Q60 58 68 54" stroke="#EA580C" stroke-width="2" fill="none" stroke-linecap="round" />
            @else
                <circle cx="52" cy="42" r="3.5" fill="#1F2937" />
                <circle cx="68" cy="42" r="3.5" fill="#1F2937" />
                <circle cx="53" cy="41" r="1.2" fill="#FFF" />
                <circle cx="69" cy="41" r="1.2" fill="#FFF" />
            @endif

            {{-- Feet --}}
            <path d="M48 94 L44 104 M48 94 L48 104 M48 94 L52 104" stroke="#EA580C" stroke-width="2.5" stroke-linecap="round" />
            <path d="M72 94 L68 104 M72 94 L72 104 M72 94 L76 104" stroke="#EA580C" stroke-width="2.5" stroke-linecap="round" />
        </svg>
    </div>

    <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">سنبل 🐦</span>
</div>
