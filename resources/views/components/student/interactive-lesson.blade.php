@php
    /** @var array<string, mixed> $lesson */
    $key = (string) ($lesson['key'] ?? 'lesson');
    $title = (string) ($lesson['title'] ?? '');
    $subtitle = (string) ($lesson['subtitle'] ?? '');
    /** @var array<int, string> $stateLabels */
    $stateLabels = $lesson['state_labels'] ?? [];
    /** @var list<array<string, mixed>> $diacriticTabs */
    $diacriticTabs = $lesson['diacritic_tabs'] ?? [];
    /** @var list<array<string, mixed>> $positions */
    $positions = $lesson['positions'] ?? [];
    /** @var array<string, mixed> $tracing */
    $tracing = $lesson['tracing'] ?? [];
    /** @var array<string, mixed> $discovery */
    $discovery = $lesson['discovery'] ?? [];
    /** @var array<string, mixed> $demo */
    $demo = $lesson['demo'] ?? [];
@endphp

<div
    {{ $attributes->class('relative flex min-h-dvh flex-col overflow-hidden text-emerald-950') }}
    data-interactive-lesson="{{ $key }}"
    data-lesson-state="{{ $currentState }}"
    dir="rtl"
    style="background:
        radial-gradient(ellipse 75% 45% at 15% 8%, rgba(251, 191, 36, 0.32), transparent 55%),
        radial-gradient(ellipse 65% 40% at 92% 18%, rgba(16, 185, 129, 0.26), transparent 50%),
        linear-gradient(165deg, #ecfdf5 0%, #d1fae5 36%, #fef3c7 70%, #fb923c 100%);"
    x-data="interactiveLessonEngine(@js([
        'completeAudio' => $tracing['complete_audio'] ?? '',
        'demoAudio' => $demo['explain_audio'] ?? '',
    ]))"
    x-init="onStateChange({{ $currentState }})"
>
    <div
        class="pointer-events-none absolute inset-0 opacity-[0.1]"
        style="background-image: repeating-linear-gradient(-14deg, transparent, transparent 20px, rgba(6,78,59,0.09) 20px, rgba(6,78,59,0.09) 21px);"
        aria-hidden="true"
    ></div>

    <header class="relative z-20 flex items-center justify-between gap-3 px-4 py-4 sm:px-6">
        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex min-h-12 min-w-12 items-center justify-center rounded-2xl bg-white/75 text-2xl shadow-sm backdrop-blur transition hover:bg-white"
            aria-label="العودة للوحة التعلم"
        >
            🔙
        </a>

        <div class="rounded-2xl bg-white/75 px-4 py-2 text-center shadow-sm backdrop-blur">
            <p class="text-xs font-semibold text-emerald-700">{{ $subtitle }}</p>
            <p class="text-sm font-black text-emerald-950">{{ $title }}</p>
        </div>

        <div class="rounded-2xl bg-emerald-900/90 px-3 py-2 text-sm font-bold text-amber-100">
            {{ $currentState }} / 5
        </div>
    </header>

    <nav class="relative z-20 mx-auto flex w-full max-w-3xl gap-2 overflow-x-auto px-4 pb-2 sm:px-6" aria-label="خطوات الدرس">
        @foreach ($stateLabels as $stateNumber => $label)
            <button
                type="button"
                wire:click="goToState({{ (int) $stateNumber }})"
                class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-2xl px-3 text-sm font-bold transition {{ (int) $currentState === (int) $stateNumber ? 'bg-emerald-700 text-white shadow' : 'bg-white/70 text-emerald-900' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="relative z-10 mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 pb-8 sm:px-6">
        {{-- State 1: Diacritics matrix --}}
        <section
            data-lesson-engine-state="1"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentState !== 1])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">الحركات الثلاث</h2>
            <p class="text-center text-base text-emerald-800">اضغط الحركة، ثم اسمع البطاقات الثلاث.</p>

            <div class="flex flex-wrap justify-center gap-3">
                @foreach ($diacriticTabs as $tab)
                    <button
                        type="button"
                        data-diacritic="{{ $tab['glyph'] }}"
                        data-audio-trigger
                        class="inline-flex min-h-12 min-w-16 items-center justify-center rounded-2xl px-4 text-3xl font-black shadow transition"
                        :class="diacriticTab === @js($tab['glyph']) ? 'bg-amber-400 text-amber-950 ring-4 ring-amber-200' : 'bg-white/80 text-emerald-900'"
                        @click="setDiacriticTab(@js($tab['glyph']))"
                        aria-label="حركة {{ $tab['glyph'] }}"
                    >
                        {{ $tab['glyph'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($diacriticTabs as $tab)
                <div
                    class="grid w-full grid-cols-1 gap-3 sm:grid-cols-3"
                    x-show="diacriticTab === @js($tab['glyph'])"
                    @if ($loop->first) @else x-cloak @endif
                >
                    @foreach ($tab['cards'] as $card)
                        <button
                            type="button"
                            data-audio-trigger
                            data-diacritic="{{ $card['highlight'] }}"
                            class="flex min-h-28 flex-col items-center justify-center gap-2 rounded-[1.5rem] border-2 border-emerald-200 bg-white/90 p-4 text-center shadow-md transition hover:border-amber-400"
                            @click="speak(@js($card['audio']))"
                        >
                            <span class="text-3xl" aria-hidden="true">{{ $card['emoji'] }}</span>
                            <span class="text-2xl font-black text-emerald-950">
                                <span class="rounded-lg bg-amber-200 px-1 text-amber-950">{{ $card['highlight'] }}</span>{{ mb_substr($card['word'], mb_strlen($card['highlight'])) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </section>

        {{-- State 2: Positions --}}
        <section
            data-lesson-engine-state="2"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentState !== 2])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">مواقع الحرف</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($positions as $position)
                    <button
                        type="button"
                        data-audio-trigger
                        class="flex min-h-36 flex-col items-center justify-center gap-3 rounded-[1.75rem] border-2 border-emerald-200 bg-white/90 p-5 shadow-md transition hover:border-amber-400"
                        @click="speak(@js($position['audio']))"
                    >
                        <span class="text-sm font-bold text-emerald-700">{{ $position['label'] }}</span>
                        <span class="flex flex-wrap items-center justify-center gap-0.5 text-4xl font-black text-emerald-950" dir="rtl">
                            @foreach ($position['parts'] as $part)
                                <span @class([
                                    'inline-flex min-h-12 min-w-12 items-center justify-center rounded-xl px-1',
                                    'bg-emerald-300 motion-safe:animate-pulse shadow-[0_0_18px_rgba(52,211,153,0.75)]' => $part['highlight'],
                                ])>{{ $part['text'] }}</span>
                            @endforeach
                        </span>
                        <span class="sr-only">{{ $position['word'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- State 3: Tracing --}}
        <section
            data-lesson-engine-state="3"
            @class(['flex flex-1 flex-col items-center gap-5 pt-2' => true, 'hidden' => $currentState !== 3])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">التتبع بالإصبع</h2>
            <p class="text-center text-base text-emerald-800">{{ $tracing['label'] ?? 'تتبّع الحرف' }}</p>

            <div
                data-trace-canvas
                class="relative w-full max-w-md touch-none rounded-[2rem] border-2 border-emerald-200 bg-white/90 p-4 shadow-lg"
                @pointerdown.prevent="startTrace($event)"
                @pointermove.prevent="moveTrace($event)"
                @pointerup.prevent="endTrace()"
                @pointerleave.prevent="endTrace()"
            >
                <svg viewBox="0 0 140 140" class="h-64 w-full select-none" aria-label="لوحة تتبع حرف الراء">
                    <path
                        d="{{ $tracing['path'] ?? '' }}"
                        fill="none"
                        stroke="#d1fae5"
                        stroke-width="14"
                        stroke-linecap="round"
                    />
                    <path
                        x-ref="guidePath"
                        d="{{ $tracing['path'] ?? '' }}"
                        fill="none"
                        stroke="#059669"
                        stroke-width="6"
                        stroke-linecap="round"
                        stroke-dasharray="4 10"
                        class="motion-safe:animate-pulse"
                    />
                    <path
                        x-ref="inkPath"
                        d="{{ $tracing['path'] ?? '' }}"
                        fill="none"
                        stroke="#b45309"
                        stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="100"
                        :stroke-dashoffset="traceOffset"
                        pathLength="100"
                    />
                </svg>
                <p class="mt-2 text-center text-sm font-semibold text-emerald-800" x-text="traceDone ? 'أحسنت! أكملت التتبع ⭐' : 'مرّر إصبعك على الخط'"></p>
            </div>

            <div class="flex justify-center" :class="traceDone && 'motion-safe:animate-bounce'">
                <x-student.mascot :state="$mascotState" message="ارسم حرف الراء معي!" size="md" />
            </div>
        </section>

        {{-- State 4: Discovery --}}
        <section
            data-lesson-engine-state="4"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentState !== 4])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">الاستكشاف البيئي</h2>
            <p class="text-center text-base text-emerald-800">{{ $discovery['intro'] ?? '' }}</p>
            <p class="text-center text-lg font-bold text-amber-800">
                نجوم الاستكشاف: <span x-text="stars"></span> ⭐
            </p>

            <div class="relative min-h-64 overflow-hidden rounded-[2rem] border border-emerald-200 bg-gradient-to-b from-lime-100 via-amber-50 to-orange-100 p-6 shadow-inner">
                <span class="absolute start-4 top-4 text-3xl opacity-70" aria-hidden="true">🌳</span>
                <span class="absolute end-6 top-10 text-3xl opacity-60" aria-hidden="true">🍊</span>
                <div class="relative z-10 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach (($discovery['items'] ?? []) as $item)
                        <button
                            type="button"
                            data-audio-trigger
                            data-discovery-item="{{ $item['id'] }}"
                            class="inline-flex min-h-24 min-w-12 flex-col items-center justify-center gap-1 rounded-2xl bg-white/85 p-3 text-center shadow transition hover:bg-white"
                            :class="found[@js($item['id'])] && 'ring-4 ring-amber-300'"
                            @click="exploreItem(@js($item))"
                        >
                            <span class="text-3xl" aria-hidden="true">{{ $item['emoji'] }}</span>
                            <span class="text-base font-bold text-emerald-950">{{ $item['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- State 5: Little teacher + CTA --}}
        <section
            data-lesson-engine-state="5"
            @class(['flex flex-1 flex-col items-center gap-6 pt-2' => true, 'hidden' => $currentState !== 5])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">المعلم الصغير</h2>
            <x-student.mascot :state="$mascotState" :message="$demo['question'] ?? ''" size="lg" />

            <div class="w-full rounded-[2rem] border border-emerald-200 bg-white/90 p-6 text-center shadow-lg sm:p-8">
                <p class="text-lg font-semibold text-emerald-800">{{ $demo['question'] ?? '' }}</p>
                <p class="mt-6 flex flex-wrap items-center justify-center gap-1 text-5xl font-black text-emerald-950 sm:text-6xl" dir="rtl">
                    @foreach (($demo['parts'] ?? []) as $part)
                        <span
                            class="inline-flex min-h-14 min-w-14 items-center justify-center rounded-xl px-2 transition"
                            @if ($part['highlight'])
                                :class="demoGlow ? 'bg-emerald-400 shadow-[0_0_24px_rgba(52,211,153,0.85)] scale-110' : 'bg-emerald-100'"
                            @endif
                        >{{ $part['text'] }}</span>
                    @endforeach
                </p>
                <button
                    type="button"
                    data-audio-trigger
                    class="mt-6 inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-amber-400 px-5 text-base font-bold text-amber-950 shadow hover:bg-amber-300"
                    @click="playDemo()"
                >
                    🔊 اسمع شرح سنبل
                </button>
            </div>

            <a
                href="{{ $quizUrl }}"
                class="inline-flex min-h-14 min-w-[min(100%,20rem)] items-center justify-center rounded-3xl bg-gradient-to-l from-amber-400 to-orange-500 px-8 text-xl font-black text-amber-950 shadow-xl transition hover:brightness-105"
            >
                {{ $demo['cta'] ?? 'جاهز للاختبار يا بطل! 🎯' }}
            </a>
        </section>

        <div class="mt-auto flex flex-wrap justify-center gap-3 pt-6">
            @if ($currentState > 1)
                <button
                    type="button"
                    wire:click="previousState"
                    class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-white/80 px-6 text-base font-bold text-emerald-900 shadow"
                >
                    السابق
                </button>
            @endif

            @if ($currentState < 5)
                <button
                    type="button"
                    wire:click="nextState"
                    class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-emerald-700 px-8 text-lg font-bold text-white shadow-lg hover:bg-emerald-600"
                >
                    التالي ←
                </button>
            @endif
        </div>
    </div>
</div>
