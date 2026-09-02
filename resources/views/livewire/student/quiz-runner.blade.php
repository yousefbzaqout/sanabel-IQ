<div
    data-quiz-immersive
    class="relative flex h-screen flex-col overflow-hidden bg-gradient-to-b from-indigo-950 via-violet-900 to-fuchsia-800 text-white"
    x-data="immersiveQuizFx()"
    x-on:quiz-correct.window="burstConfetti(); $store.audio?.playFx('correct')"
    x-on:quiz-incorrect.window="$store.audio?.playFx('retry')"
    x-on:quiz-speak.window="$store.audio?.speak($event.detail.message || $event.detail[0]?.message || '')"
    x-init="
        $store.audio?.speak(@js($this->currentQuestion?->prompt ?? $this->mascotMessage));
    "
>
    <canvas
        x-ref="confetti"
        class="pointer-events-none absolute inset-0 z-50 h-full w-full"
        aria-hidden="true"
    ></canvas>

    {{-- Immersive header --}}
    <header class="relative z-20 flex items-center justify-between gap-3 px-4 py-4 sm:px-6">
        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-2xl shadow-lg backdrop-blur transition hover:bg-white/25"
            aria-label="العودة للوحة التعلم"
            title="رجوع"
        >
            🔙
        </a>

        <div class="flex items-center gap-3 rounded-2xl bg-white/10 px-3 py-2 backdrop-blur">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-300 text-lg font-bold text-amber-950">
                {{ mb_substr($this->student->name, 0, 1) }}
            </div>
            <div class="leading-tight">
                <p class="text-sm font-semibold">{{ $this->student->name }}</p>
                <p class="text-xs text-amber-200">🔥 {{ (int) ($this->student->streak?->current_streak ?? 0) }}</p>
            </div>
        </div>

        <div class="rounded-2xl bg-white/15 px-4 py-2 text-sm font-bold backdrop-blur" dir="rtl">
            السؤال {{ min($currentIndex + 1, $this->totalQuestions) }} / {{ $this->totalQuestions }}
        </div>
    </header>

    {{-- Gamified progress --}}
    <div class="relative z-20 px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <div class="h-4 flex-1 overflow-hidden rounded-full bg-white/20 shadow-inner">
                <div
                    data-quiz-progress="{{ $progressPercent }}"
                    class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-lime-300 transition-all duration-500 ease-out"
                    style="width: {{ $progressPercent }}%"
                ></div>
            </div>
            <span class="text-2xl drop-shadow">⭐</span>
        </div>
    </div>

    <div class="relative z-20 mx-auto mt-4 w-full max-w-3xl flex-1 overflow-y-auto px-4 pb-8 sm:px-6">
        @if ($completed && $result)
            <div class="flex flex-col items-center gap-6 pt-6 text-center">
                <x-student.mascot
                    :state="$mascotState"
                    :message="$this->mascotMessage"
                    size="lg"
                />
                <div class="rounded-3xl bg-white/15 p-8 shadow-2xl backdrop-blur">
                    <h2 class="text-3xl font-black">انتهى الاختبار!</h2>
                    <p class="mt-3 text-lg text-amber-100">
                        النتيجة: {{ $result['score'] }} / {{ $result['total_questions'] }}
                        ({{ $result['percentage'] }}%)
                    </p>
                    <p class="mt-2 text-2xl font-bold text-lime-300" x-show="{{ $result['xp_earned'] > 0 ? 'true' : 'false' }}">
                        +{{ $result['xp_earned'] }} XP 🎉
                    </p>
                    <a
                        href="{{ route('student.dashboard') }}"
                        class="mt-6 inline-flex rounded-2xl bg-amber-300 px-6 py-3 text-base font-bold text-amber-950 shadow-lg hover:bg-amber-200"
                    >
                        العودة للمسار
                    </a>
                </div>
            </div>
        @elseif ($this->currentQuestion)
            <div class="flex flex-col items-center gap-5 pt-2">
                <x-student.mascot
                    :state="$mascotState"
                    :message="$this->mascotMessage"
                    size="md"
                />

                <div class="w-full rounded-3xl bg-white/95 p-6 text-slate-900 shadow-2xl dark:bg-slate-900/90 dark:text-white">
                    <div class="mb-5 flex items-start gap-3">
                        <h2 class="flex-1 text-xl font-extrabold leading-relaxed sm:text-2xl" dir="auto">
                            {{ $this->currentQuestion->prompt }}
                        </h2>
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl shadow"
                            wire:click="$dispatch('quiz-speak', { message: @js($this->currentQuestion->prompt) })"
                            x-on:click="$store.audio?.speak(@js($this->currentQuestion->prompt))"
                            aria-label="سماع السؤال"
                        >
                            🔊
                        </button>
                    </div>

                    <div class="grid gap-4">
                        @foreach ($this->currentQuestion->options->sortBy('order_column')->values() as $optionIndex => $option)
                            @php
                                $palette = ['from-sky-500 to-blue-600', 'from-emerald-500 to-teal-600', 'from-violet-500 to-purple-600', 'from-rose-500 to-pink-600'];
                                $gradient = $palette[$optionIndex % count($palette)];
                                $isSelected = $selectedOptionId === $option->id;
                                $isCorrectReveal = $feedbackLocked && $revealedCorrectOptionId === $option->id;
                                $isWrongReveal = $feedbackLocked && $isSelected && ! $lastAnswerCorrect;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectAnswer({{ $option->id }})"
                                wire:loading.attr="disabled"
                                @disabled($feedbackLocked)
                                @click="$store.audio?.playFx('click')"
                                @class([
                                    'relative w-full rounded-2xl border-b-8 px-5 py-5 text-start text-lg font-bold text-white shadow-[0_10px_0_rgba(0,0,0,0.25)] transition active:translate-y-1 active:border-b-4 active:shadow-md disabled:cursor-not-allowed sm:text-xl',
                                    "bg-gradient-to-br {$gradient} border-black/20" => ! $feedbackLocked,
                                    'bg-emerald-500 border-emerald-700 ring-4 ring-emerald-200' => $isCorrectReveal,
                                    'bg-orange-400 border-orange-600 ring-4 ring-orange-200' => $isWrongReveal,
                                    'bg-slate-400 border-slate-500 opacity-70' => $feedbackLocked && ! $isCorrectReveal && ! $isWrongReveal,
                                ])
                            >
                                <span dir="auto">{{ $option->option_text }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if ($feedbackLocked)
                        <div class="mt-6 flex justify-center">
                            <button
                                type="button"
                                wire:click="advanceAfterFeedback"
                                class="rounded-2xl bg-indigo-600 px-6 py-3 text-base font-bold text-white shadow-lg hover:bg-indigo-500"
                            >
                                {{ $currentIndex >= $this->totalQuestions - 1 ? 'عرض النتيجة' : 'السؤال التالي' }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
