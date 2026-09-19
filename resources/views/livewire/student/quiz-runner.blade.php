@php
    $questionNumber = min($currentIndex + 1, max(1, $this->totalQuestions));
    $totalQuestions = max(1, $this->totalQuestions);
    $streakDays = (int) ($this->student->streak?->current_streak ?? 0);
    $studentXp = (int) ($this->student->total_xp ?? 0);
    // Avoid Storage::exists/lastModified on every Livewire morph — that I/O stalls question changes.
    $questionAudioUrl = filled($this->currentQuestion?->audio_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->currentQuestion->audio_path)
        : null;
    $optionLetters = ['أ', 'ب', 'ج', 'د', 'هـ', 'و'];
@endphp

<div
    data-quiz-immersive
    data-question-id="{{ $this->currentQuestion?->id ?? 0 }}"
    class="relative flex min-h-dvh flex-col overflow-y-auto bg-background text-on-surface font-body-md text-body-md antialiased pb-[env(safe-area-inset-bottom,1rem)]"
    x-data="immersiveQuizFx({
        questionId: {{ (int) ($this->currentQuestion?->id ?? 0) }},
        audioUrl: @js($questionAudioUrl),
        prompt: @js($this->currentQuestion?->prompt ?? ''),
    })"
    x-on:quiz-correct.window="burstConfetti(); $store.audio?.playFx('correct')"
    x-on:quiz-incorrect.window="$store.audio?.playFx('retry')"
    wire:key="quiz-shell-{{ $this->currentQuestion?->id ?? 'done' }}"
>
    <canvas
        x-ref="confetti"
        class="pointer-events-none absolute inset-0 z-50 h-full w-full"
        aria-hidden="true"
    ></canvas>

    <div class="w-full max-w-6xl mx-auto px-gutter-mobile md:px-margin py-space-md md:py-space-lg flex flex-col gap-space-lg relative z-20">
        {{-- Top Floating Meta Status Bar --}}
        <section class="w-full bg-surface-container-lowest rounded-lg p-space-md shadow-sm flex flex-col md:flex-row items-center justify-between gap-space-md relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-32 h-32 bg-primary-fixed/30 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-secondary-fixed/30 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center gap-space-sm w-full md:w-auto justify-between md:justify-start">
                <a
                    href="{{ route('student.dashboard') }}"
                    class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-high text-on-surface hover:bg-error-container hover:text-on-error-container transition-all active:scale-95 shadow-sm group"
                    aria-label="العودة للوحة التعلم"
                >
                    <span class="material-symbols-outlined text-headline-sm transition-transform group-hover:translate-x-1">arrow_forward</span>
                    <span class="font-label-lg text-label-lg font-bold">خروج من التحدي</span>
                </a>
            </div>

            <div class="flex-1 w-full max-w-lg flex flex-col gap-space-xs">
                <div class="flex justify-between items-center px-space-xs">
                    <div class="flex items-center gap-space-xs">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary animate-ping"></span>
                        <span class="font-label-lg text-label-lg font-bold text-primary">
                            السؤال {{ $questionNumber }} من {{ $totalQuestions }}
                        </span>
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant bg-surface-container px-space-sm py-0.5 rounded-full">
                        {{ $this->material->title }}
                    </span>
                </div>
                <div class="relative w-full h-4 bg-surface-container-high rounded-full overflow-visible p-0.5 flex items-center">
                    <div
                        data-quiz-progress="{{ $progressPercent }}"
                        class="h-full bg-gradient-to-l from-primary-container to-primary rounded-full transition-all duration-700 ease-out shadow-inner"
                        style="width: {{ $progressPercent }}%;"
                    ></div>
                    <div
                        class="absolute top-1/2 -translate-y-1/2 translate-x-1/2 flex items-center justify-center w-7 h-7 rounded-full bg-primary-container text-on-primary-container shadow-md ring-4 ring-surface-container-lowest motion-safe:animate-bounce"
                        style="left: {{ $progressPercent }}%;"
                    >
                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-space-sm">
                <div class="flex items-center gap-space-xs px-space-md py-space-xs bg-error-container/60 text-on-error-container rounded-full shadow-sm">
                    <span class="material-symbols-outlined text-error text-body-lg" style="font-variation-settings: 'FILL' 1;">favorite</span>
                    <span class="font-label-lg text-label-lg font-bold">{{ $this->student->name }}</span>
                </div>
                <div class="flex items-center gap-space-xs px-space-md py-space-xs bg-secondary-fixed text-on-secondary-fixed rounded-full shadow-sm">
                    <span class="material-symbols-outlined text-secondary text-body-lg" style="font-variation-settings: 'FILL' 1;">bolt</span>
                    <span class="font-label-lg text-label-lg font-bold">⭐ {{ $studentXp }}</span>
                </div>
                <div class="flex items-center gap-space-xs px-space-md py-space-xs bg-primary-fixed text-on-primary-fixed rounded-full shadow-sm">
                    <span class="font-label-lg text-label-lg font-bold">🔥 {{ $streakDays }} أيام</span>
                </div>
            </div>
        </section>

        @if ($completed && $result)
            <section class="bg-surface-container-lowest rounded-lg p-space-xl shadow-md flex flex-col items-center gap-space-md text-center">
                <x-student.mascot :state="$mascotState" :message="$this->mascotMessage" size="lg" />
                <h2 class="font-headline-lg text-headline-lg text-on-surface font-extrabold">انتهى الاختبار!</h2>
                <p class="font-body-lg text-body-lg text-on-surface-variant">
                    النتيجة: {{ $result['score'] }} / {{ $result['total_questions'] }}
                    ({{ $result['percentage'] }}%)
                </p>
                @if ($result['xp_earned'] > 0)
                    <p class="font-headline-md text-headline-md text-primary font-bold">+{{ $result['xp_earned'] }} XP 🎉</p>
                @endif
                <a
                    href="{{ route('student.dashboard') }}"
                    class="mt-space-sm inline-flex items-center justify-center px-space-xl py-space-md rounded-full bg-primary-container text-on-primary-container font-headline-sm text-headline-sm font-extrabold shadow-[0_6px_0_#613b00]"
                >
                    العودة للمسار
                </a>
            </section>
        @elseif ($this->currentQuestion)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
                <aside class="lg:col-span-4 flex flex-col items-center gap-space-md text-center">
                    <div class="w-full relative bg-surface-container-lowest rounded-lg p-space-lg shadow-md flex flex-col gap-space-sm text-right">
                        <div class="flex items-center justify-between">
                            <span class="font-label-sm text-label-sm font-bold text-secondary bg-secondary-container/50 px-space-sm py-space-xs rounded-full">المرشد سنبل يهمس لكِ 🐥</span>
                            <span class="text-headline-sm">💡</span>
                        </div>
                        <p class="font-headline-sm text-headline-sm text-on-surface font-bold leading-relaxed">
                            {{ $this->mascotMessage }}
                        </p>
                        <div class="w-5 h-5 bg-surface-container-lowest rotate-45 absolute -bottom-2.5 right-12 shadow-sm rounded-sm"></div>
                    </div>

                    <div class="relative group mt-space-xs">
                        <div class="absolute inset-0 bg-gradient-to-tr from-primary-fixed to-secondary-fixed rounded-full blur-3xl opacity-70 scale-95 group-hover:scale-105 transition-transform duration-500"></div>
                        <div class="relative w-56 h-56 sm:w-64 sm:h-64 rounded-xl overflow-hidden bg-surface-container-lowest shadow-xl flex items-center justify-center p-space-sm">
                            <img
                                src="{{ asset('brand/mascot-sanbal-storybook.jpg') }}"
                                alt="سنبل المرشد"
                                class="w-full h-full object-contain transform group-hover:scale-105 transition-transform duration-300"
                            >
                            <div class="sr-only">
                                <x-student.mascot :state="$mascotState" :message="$this->mascotMessage" size="sm" :show-bubble="false" />
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-space-md inline-flex items-center gap-space-xs px-space-lg py-space-sm rounded-full bg-secondary-container text-on-secondary-container font-label-lg text-label-lg font-bold shadow-md hover:scale-105 active:scale-95 transition-all"
                            data-tts-text="{{ $this->currentQuestion->prompt }}"
                            @if ($questionAudioUrl)
                                data-static-audio="{{ $questionAudioUrl }}"
                            @endif
                            x-on:click.prevent="(() => {
                                const audio = $store.audio;
                                if (!audio) { return; }
                                const url = @js($questionAudioUrl);
                                const text = @js($this->currentQuestion->prompt);
                                // Prefer static MP3; live speak uses fast path (server cache / native fallback).
                                if (url && audio.playStatic) { audio.playStatic(url, text); return; }
                                audio.speakFast?.(text) ?? audio.speak?.(text);
                            })()"
                            aria-label="استمع للسؤال بصوت سنبل"
                        >
                            <span class="material-symbols-outlined text-secondary animate-pulse" style="font-variation-settings: 'FILL' 1;">volume_up</span>
                            <span>استمع للسؤال بصوت سنبل</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-space-xs px-space-md py-space-xs bg-surface-container-low rounded-full">
                        <span class="material-symbols-outlined text-secondary text-sm">verified_user</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">الذكاء الاصطناعي يُكيّف صعوبة الأسئلة حسب تقدمك</span>
                    </div>
                </aside>

                <div class="lg:col-span-8 flex flex-col gap-space-lg" wire:key="quiz-question-{{ $this->currentQuestion->id }}">
                    <div class="bg-surface-container-lowest rounded-lg p-space-lg md:p-space-xl shadow-md relative overflow-hidden">
                        <div class="flex items-center gap-space-xs mb-space-sm">
                            <span class="px-space-md py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-bold">تحدي الفهم</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">اختر الإجابة الأدق</span>
                        </div>
                        <h2 class="font-headline-lg text-headline-lg text-on-surface font-extrabold mb-space-md leading-tight" dir="auto">
                            {{ $this->currentQuestion->prompt }}
                        </h2>

                        <div class="grid grid-cols-1 gap-space-md" id="quiz-options-group">
                            @foreach ($this->currentQuestion->options->sortBy('order_column')->values() as $optionIndex => $option)
                                @php
                                    $isSelected = $selectedOptionId === $option->id;
                                    $isCorrectReveal = $feedbackLocked && $revealedCorrectOptionId === $option->id;
                                    $isWrongReveal = $feedbackLocked && $isSelected && ! $lastAnswerCorrect;
                                    $letter = $optionLetters[$optionIndex] ?? (string) ($optionIndex + 1);
                                    $optionAudioUrl = filled($option->audio_path)
                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($option->audio_path)
                                        : null;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectAnswer({{ $option->id }})"
                                    wire:loading.attr="disabled"
                                    @disabled($feedbackLocked)
                                    @click="$store.audio?.playFx('click')"
                                    @if ($optionAudioUrl)
                                        data-static-audio="{{ $optionAudioUrl }}"
                                    @endif
                                    @class([
                                        'w-full text-right p-space-lg rounded-lg flex items-center justify-between relative transition-transform transform active:translate-y-1 group disabled:cursor-not-allowed',
                                        'bg-secondary-container/40 text-on-secondary-container shadow-md' => $isCorrectReveal,
                                        'bg-error-container/50 text-on-error-container shadow-md' => $isWrongReveal,
                                        'bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container-low' => ! $isCorrectReveal && ! $isWrongReveal,
                                        'opacity-60' => $feedbackLocked && ! $isCorrectReveal && ! $isWrongReveal,
                                    ])
                                    style="box-shadow: 0 6px 0 {{ $isCorrectReveal ? '#006a61' : ($isWrongReveal ? '#93000a' : '#dae2fd') }};"
                                >
                                    <div class="flex items-center gap-space-md">
                                        <span @class([
                                            'w-12 h-12 rounded-full flex items-center justify-center font-headline-sm text-headline-sm font-extrabold shadow-sm',
                                            'bg-secondary text-on-secondary' => $isCorrectReveal,
                                            'bg-error text-on-error' => $isWrongReveal,
                                            'bg-surface-container-high text-on-surface group-hover:bg-primary group-hover:text-on-primary transition-colors' => ! $isCorrectReveal && ! $isWrongReveal,
                                        ])>
                                            {{ $letter }}
                                        </span>
                                        <span class="font-headline-sm text-headline-sm font-bold" dir="auto">{{ $option->option_text }}</span>
                                    </div>
                                    @if ($isCorrectReveal)
                                        <div class="flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-secondary text-on-secondary shadow-sm motion-safe:animate-bounce">
                                            <span class="material-symbols-outlined text-body-md" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                            <span class="font-label-lg text-label-lg font-bold">إجابة عبقرية!</span>
                                        </div>
                                    @elseif ($isWrongReveal)
                                        <span class="material-symbols-outlined text-error">cancel</span>
                                    @else
                                        <span class="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">radio_button_unchecked</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($feedbackLocked)
                        <section class="w-full bg-surface-container-lowest rounded-lg p-space-md md:p-space-lg shadow-xl flex flex-col md:flex-row items-center justify-between gap-space-md relative overflow-hidden">
                            <div class="absolute inset-y-0 right-0 w-3 {{ $lastAnswerCorrect ? 'bg-secondary' : 'bg-error' }}"></div>
                            <div class="flex items-center gap-space-md text-right w-full md:w-auto">
                                <div @class([
                                    'w-14 h-14 rounded-full flex items-center justify-center shadow-sm',
                                    'bg-secondary-fixed text-on-secondary-fixed' => $lastAnswerCorrect,
                                    'bg-error-container text-on-error-container' => ! $lastAnswerCorrect,
                                ])>
                                    <span class="material-symbols-outlined text-headline-md" style="font-variation-settings: 'FILL' 1;">
                                        {{ $lastAnswerCorrect ? 'military_tech' : 'lightbulb' }}
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span @class([
                                        'font-headline-sm text-headline-sm font-black',
                                        'text-secondary' => $lastAnswerCorrect,
                                        'text-error' => ! $lastAnswerCorrect,
                                    ])>
                                        {{ $this->mascotMessage }}
                                    </span>
                                    @if ($lastAnswerCorrect)
                                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                                            تقدّم رائع! أكملت {{ $correctCount }} من {{ $totalQuestions }} بنجاح.
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-space-md w-full md:w-auto justify-end">
                                <button
                                    type="button"
                                    wire:click="advanceAfterFeedback"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-70 pointer-events-none"
                                    class="w-full md:w-auto px-space-xl py-space-md rounded-full bg-primary-container text-on-primary-container font-headline-sm text-headline-sm font-extrabold flex items-center justify-center gap-space-sm transition-all transform hover:-translate-y-0.5 active:translate-y-1"
                                    style="box-shadow: 0 6px 0 #613b00, inset 0 2px 0 rgba(255,255,255,0.4);"
                                >
                                    <span wire:loading.remove wire:target="advanceAfterFeedback">{{ $currentIndex >= $this->totalQuestions - 1 ? 'عرض النتيجة' : 'السؤال التالي' }}</span>
                                    <span wire:loading wire:target="advanceAfterFeedback">جاري التحميل…</span>
                                    <span class="material-symbols-outlined text-headline-md" wire:loading.remove wire:target="advanceAfterFeedback">rocket_launch</span>
                                </button>
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
