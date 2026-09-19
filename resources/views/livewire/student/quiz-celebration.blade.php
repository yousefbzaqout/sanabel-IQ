@php
    $studentName = $this->student->name;
    $totalXp = (int) ($this->student->total_xp ?? 0) + $xpEarned;
    $accuracyBars = max(0, min(5, (int) round($percentage / 20)));
@endphp

<div
    data-quiz-celebration
    data-confetti-blast
    class="relative flex min-h-dvh flex-col overflow-y-auto bg-background text-on-surface font-body-md text-body-md antialiased select-none pb-[env(safe-area-inset-bottom,1rem)]"
    x-data="immersiveQuizFx()"
    x-init="
        celebrationBlast();
        $store.audio?.playFx('fanfare');
        $store.audio?.speak(@js('أحسنت يا بطل! لقد أنهيت التحدي بنجاح!'));
    "
>
    <canvas
        x-ref="confetti"
        class="pointer-events-none absolute inset-0 z-50 h-full w-full"
        aria-hidden="true"
    ></canvas>

    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0" id="confetti-container" aria-hidden="true">
        <div class="absolute -top-12 right-1/4 w-72 h-72 rounded-full bg-primary-container/15 blur-3xl"></div>
        <div class="absolute top-40 left-10 w-80 h-80 rounded-full bg-secondary-fixed/40 blur-3xl"></div>
        <div class="absolute top-1/2 right-10 w-96 h-96 rounded-full bg-tertiary-container/30 blur-3xl"></div>
        <span class="absolute top-16 right-[12%] text-primary-container text-4xl motion-safe:animate-bounce" style="animation-duration: 2.8s;">✨</span>
        <span class="absolute top-28 left-[14%] text-secondary text-3xl motion-safe:animate-bounce" style="animation-duration: 3.4s;">⭐</span>
        <span class="absolute top-64 right-[8%] text-tertiary text-2xl motion-safe:animate-pulse">🌟</span>
    </div>

    <div class="relative z-10 w-full max-w-5xl mx-auto px-margin-mobile md:px-margin py-space-md md:py-space-lg flex flex-col items-center">
        <p
            data-reduced-motion-fallback
            class="rounded-2xl bg-surface-container-low px-4 py-2 text-center text-sm font-semibold text-on-surface-variant mb-space-sm"
            dir="rtl"
        >
            أحسنت! احتفال هادئ بدون مؤثرات حركة.
        </p>

        <div class="flex items-center gap-space-xs px-space-lg py-space-xs rounded-full bg-primary-fixed text-on-primary-fixed shadow-sm mb-space-md motion-safe:animate-pulse">
            <span class="text-xl">🏆</span>
            <span class="font-label-lg text-label-lg font-bold">اكتمال المرحلة بامتياز {{ $percentage }}%</span>
            <span class="text-xl">✨</span>
        </div>

        <div class="relative w-full flex flex-col items-center justify-center -mt-2 mb-space-md">
            <div class="relative flex items-center justify-center">
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-gradient-to-b from-primary-fixed via-primary-container to-primary flex items-center justify-center shadow-xl shadow-primary/20">
                        <span class="material-symbols-outlined text-surface-container-lowest text-6xl md:text-7xl" style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                    </div>
                    <div class="flex items-center gap-1 -mt-4 bg-surface-container-lowest text-primary px-space-sm py-0.5 rounded-full shadow-md font-label-sm text-label-sm font-bold">
                        <span>⭐⭐⭐⭐⭐</span>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-2xl mt-space-md grid grid-cols-1 md:grid-cols-12 gap-space-md items-center">
                <div class="md:col-span-5 flex flex-col items-center justify-center relative">
                    <div class="relative group">
                        <div class="w-44 h-44 md:w-52 md:h-52 rounded-2xl bg-surface-container-low p-2 shadow-md flex items-center justify-center overflow-hidden">
                            <img
                                src="{{ asset('brand/mascot-sanbal-storybook.jpg') }}"
                                alt="سنبل يحتفل"
                                class="w-full h-full object-cover rounded-xl transform group-hover:scale-105 transition-transform duration-300"
                            >
                        </div>
                        <div class="absolute -bottom-3 -right-2 bg-secondary text-on-secondary px-space-md py-1 rounded-full shadow-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">military_tech</span>
                            <span class="font-label-sm text-label-sm font-bold">بطل اليوم</span>
                        </div>
                    </div>
                    <div class="mt-space-md">
                        <x-student.mascot state="happy" size="sm" message="واو! أداء رائع يا بطل!" />
                    </div>
                </div>

                <div class="md:col-span-7 flex flex-col justify-center">
                    <div class="relative bg-surface-container-lowest p-space-lg rounded-2xl md:rounded-3xl shadow-md text-right">
                        <div class="flex items-center gap-space-xs mb-space-xs text-primary font-headline-sm text-headline-sm font-bold">
                            <span>سنبل يحييكِ بحرارة!</span>
                            <span class="text-2xl motion-safe:animate-spin" style="animation-duration: 6s;">🌻</span>
                        </div>
                        <p class="font-body-lg text-body-lg text-on-surface leading-relaxed font-bold">
                            «مذهلة يا {{ $studentName }}! لقد أظهرتِ براعة فائقة وحصلتِ على {{ $percentage }}% في تحدي اليوم! فخور بكِ جداً!» 🎉✨
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-space-lg max-w-2xl">
                <h1 class="font-headline-lg text-headline-lg md:text-4xl text-on-surface font-extrabold tracking-tight mb-space-xs">
                    أحسنت! أنهيت الاختبار 🎉
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">
                    أظهرتِ مهارة فائقة في {{ $this->material->title }}!
                </p>
            </div>
        </div>

        <div
            class="w-full grid grid-cols-1 md:grid-cols-3 gap-space-md my-space-md"
            dir="rtl"
            x-data="{
                xp: 0,
                accuracy: 0,
                streak: 0,
                targetXp: {{ $xpEarned }},
                targetAccuracy: {{ $percentage }},
                targetStreak: {{ $streakDays }},
                startCountUp() {
                    const animate = (key, target, duration = 1200) => {
                        const start = performance.now();
                        const tick = (now) => {
                            const progress = Math.min(1, (now - start) / duration);
                            const eased = 1 - Math.pow(1 - progress, 3);
                            this[key] = Math.round(target * eased);
                            if (progress < 1) requestAnimationFrame(tick);
                        };
                        requestAnimationFrame(tick);
                    };
                    animate('xp', this.targetXp);
                    animate('accuracy', this.targetAccuracy);
                    animate('streak', this.targetStreak);
                }
            }"
            x-init="startCountUp()"
        >
            <div
                data-stat-xp="{{ $xpEarned }}"
                class="group relative bg-gradient-to-br from-primary-fixed to-surface-container-lowest p-space-lg rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between overflow-hidden"
            >
                <div class="flex items-center justify-between mb-space-md">
                    <div class="w-12 h-12 rounded-2xl bg-primary-container text-on-primary-container flex items-center justify-center shadow-inner">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                    </div>
                    <span class="px-space-sm py-0.5 rounded-full bg-surface-container-lowest text-primary font-label-md text-label-md font-bold shadow-sm">+{{ $xpEarned }} XP مضافة</span>
                </div>
                <div>
                    <div class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant mb-1 font-bold">نقاط الخبرة والمعرفة</div>
                    <div class="font-headline-lg text-headline-lg text-primary font-black mb-1">
                        +<span x-text="xp">{{ $xpEarned }}</span> XP
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant font-bold">إجمالي رصيدك: {{ $totalXp }} XP ⭐</p>
                    <span class="sr-only">+{{ $xpEarned }} XP</span>
                </div>
            </div>

            <div
                data-stat-accuracy="{{ $percentage }}"
                class="group relative bg-gradient-to-br from-secondary-fixed/40 to-surface-container-lowest p-space-lg rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between overflow-hidden"
            >
                <div class="flex items-center justify-between mb-space-md">
                    <div class="w-12 h-12 rounded-2xl bg-secondary text-on-secondary flex items-center justify-center shadow-inner">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">track_changes</span>
                    </div>
                    <span class="px-space-sm py-0.5 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-md text-label-md font-bold shadow-sm">دقة الإجابات 🎯</span>
                </div>
                <div>
                    <div class="font-label-sm text-label-sm uppercase tracking-wider text-secondary mb-1 font-bold">دقة الإجابات</div>
                    <div class="font-headline-lg text-headline-lg text-secondary font-black mb-1">
                        دقة <span x-text="accuracy">{{ $percentage }}</span>%
                    </div>
                    <span class="sr-only">دقة {{ $percentage }}%</span>
                </div>
                <div class="grid grid-cols-5 gap-1.5 mt-space-md">
                    @for ($i = 1; $i <= 5; $i++)
                        <div @class(['h-2.5 rounded-full', 'bg-secondary' => $i <= $accuracyBars, 'bg-surface-container-high' => $i > $accuracyBars])></div>
                    @endfor
                </div>
            </div>

            <div
                data-stat-streak="{{ $streakDays }}"
                class="group relative bg-gradient-to-br from-tertiary-fixed/60 to-surface-container-lowest p-space-lg rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between overflow-hidden"
            >
                <div class="flex items-center justify-between mb-space-md">
                    <div class="w-12 h-12 rounded-2xl bg-tertiary text-on-tertiary flex items-center justify-center shadow-inner">
                        <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                    </div>
                    <span class="px-space-sm py-0.5 rounded-full bg-tertiary-container text-on-tertiary-container font-label-md text-label-md font-bold shadow-sm">شعلة حارقة 🔥</span>
                </div>
                <div>
                    <div class="font-label-sm text-label-sm uppercase tracking-wider text-tertiary mb-1 font-bold">سلسلة المواظبة اليومية</div>
                    <div class="font-headline-lg text-headline-lg text-tertiary font-black mb-1">
                        🔥 سلسلة <span x-text="streak">{{ $streakDays }}</span> أيام!
                    </div>
                    <span class="sr-only">سلسلة {{ $streakDays }} أيام</span>
                </div>
            </div>
        </div>

        @if ($this->unlockedBadges->isNotEmpty())
            <div class="w-full my-space-sm bg-gradient-to-r from-surface-container-highest via-surface-container-lowest to-surface-container-highest p-space-md md:p-space-lg rounded-2xl md:rounded-3xl shadow-md">
                <x-student.badge-showcase :badges="$this->unlockedBadges" />
            </div>
        @endif

        <div class="w-full flex flex-col sm:flex-row items-center justify-center gap-space-md mt-space-md mb-space-lg">
            <a
                href="{{ route('student.dashboard') }}"
                class="w-full sm:w-auto px-space-xl py-space-md rounded-full bg-surface-container-lowest text-secondary hover:bg-surface-container-low transition-all duration-200 active:scale-95 shadow-sm flex items-center justify-center gap-space-xs font-label-lg text-label-lg font-bold"
            >
                <span class="material-symbols-outlined">map</span>
                <span>العودة لخريطة المغامرة</span>
            </a>
            <a
                href="{{ $this->nextChallengeUrl }}"
                class="w-full sm:w-auto px-space-xl py-space-md rounded-full bg-primary-container text-on-primary-container hover:brightness-105 active:translate-y-1 transition-all duration-150 shadow-[0_6px_0_#b45309] active:shadow-[0_1px_0_#b45309] flex items-center justify-center gap-space-sm font-headline-sm text-headline-sm font-extrabold group"
            >
                <span>تحدي جديد مع سنبل</span>
                <span class="material-symbols-outlined text-2xl group-hover:-translate-x-1 transition-transform">rocket_launch</span>
            </a>
        </div>
    </div>
</div>
