<div
    data-quiz-celebration
    data-confetti-blast
    class="relative flex h-screen flex-col overflow-hidden bg-gradient-to-b from-emerald-950 via-teal-900 to-cyan-800 text-white"
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

    <div class="relative z-20 flex flex-1 flex-col items-center justify-center gap-6 px-4 py-8 sm:px-6">
        <x-student.mascot
            state="happy"
            size="lg"
            message="واو! أداء رائع يا بطل!"
        />

        <h1 class="text-center text-3xl font-black tracking-tight sm:text-4xl" dir="rtl">
            أحسنت! أنهيت الاختبار 🎉
        </h1>

        <div
            class="grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3"
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
                class="rounded-3xl bg-white/10 p-5 text-center shadow-lg backdrop-blur"
            >
                <p class="text-sm text-emerald-100/80">نقاط الخبرة</p>
                <p class="mt-2 text-3xl font-black text-lime-300">
                    +<span x-text="xp">{{ $xpEarned }}</span> XP
                </p>
                <span class="sr-only">+{{ $xpEarned }} XP</span>
            </div>

            <div
                data-stat-accuracy="{{ $percentage }}"
                class="rounded-3xl bg-white/10 p-5 text-center shadow-lg backdrop-blur"
            >
                <p class="text-sm text-emerald-100/80">الدقة</p>
                <p class="mt-2 text-3xl font-black text-sky-200">
                    دقة <span x-text="accuracy">{{ $percentage }}</span>%
                </p>
                <span class="sr-only">دقة {{ $percentage }}%</span>
            </div>

            <div
                data-stat-streak="{{ $streakDays }}"
                class="rounded-3xl bg-white/10 p-5 text-center shadow-lg backdrop-blur"
            >
                <p class="text-sm text-emerald-100/80">السلسلة</p>
                <p class="mt-2 text-3xl font-black text-amber-200">
                    🔥 سلسلة <span x-text="streak">{{ $streakDays }}</span> أيام!
                </p>
                <span class="sr-only">سلسلة {{ $streakDays }} أيام</span>
            </div>
        </div>

        <x-student.badge-showcase :badges="$this->unlockedBadges" />
    </div>

    <div class="relative z-20 flex flex-col gap-3 px-4 pb-8 sm:flex-row sm:justify-center sm:px-6">
        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-6 py-4 text-lg font-bold text-emerald-950 shadow-lg transition hover:bg-emerald-300"
        >
            العودة للخريطة 🗺️
        </a>
        <a
            href="{{ $this->nextChallengeUrl }}"
            class="inline-flex items-center justify-center rounded-2xl border-2 border-white/40 bg-white/10 px-6 py-4 text-lg font-bold text-white shadow-lg backdrop-blur transition hover:bg-white/20"
        >
            تحدي جديد 🎯
        </a>
    </div>
</div>
