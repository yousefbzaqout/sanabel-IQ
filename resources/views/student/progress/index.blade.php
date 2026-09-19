@php
    $streakDays = (int) ($student->streak?->current_streak ?? 0);
    $xpToNext = max(0, $xpRequiredForNextLevel - $xpTowardsNextLevel);
@endphp

<x-student-layout>
    <div class="w-full max-w-7xl mx-auto px-gutter py-space-md flex flex-col gap-space-xl pt-28 md:pt-36" dir="rtl">
        <section class="relative overflow-hidden bg-gradient-to-l from-primary-fixed via-primary-fixed/40 to-surface-container-lowest rounded-xl p-space-lg md:p-space-xl shadow-[0_12px_32px_-4px_rgba(245,158,11,0.18)]">
            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-space-lg">
                <div class="flex flex-col items-center lg:items-start text-center lg:text-right gap-space-sm max-w-2xl">
                    <div class="inline-flex items-center gap-space-xs px-space-md py-1 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md font-extrabold shadow-sm">
                        <span class="material-symbols-outlined text-base">auto_awesome</span>
                        <span>لوحة تقدم {{ $student->name }}</span>
                    </div>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">
                        مسار المغامرة والبطولات! 🌟
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        صديقك <span class="font-bold text-primary">سُنبُل</span> يصفق لك! المستوى الحالي
                        <strong>{{ $level }}</strong> • ترتيب الصف #{{ $gradeRank }}
                    </p>
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-space-sm pt-space-xs">
                        <a href="{{ route('student.badges') }}" class="inline-flex items-center gap-space-xs px-space-lg py-space-sm rounded-full bg-primary-container text-on-primary-container font-label-lg text-label-lg font-extrabold shadow-md hover:scale-105 transition-all">
                            <span class="material-symbols-outlined text-xl">military_tech</span>
                            خزانة الجوائز
                        </a>
                        <a href="{{ route('student.leaderboard') }}" class="inline-flex items-center gap-space-xs px-space-md py-space-sm rounded-full bg-surface-container-lowest text-secondary font-label-lg text-label-lg font-bold shadow-sm hover:bg-surface-container transition-all">
                            <span class="material-symbols-outlined text-lg">leaderboard</span>
                            المتصدرون
                        </a>
                    </div>
                </div>
                <div class="relative w-44 h-44 sm:w-52 sm:h-52 rounded-full bg-surface-container-lowest flex items-center justify-center p-3 shadow-md">
                    <img class="relative z-10 w-full h-full object-cover rounded-full" alt="سنبل" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                </div>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-xl p-space-lg md:p-space-xl shadow-sm flex flex-col gap-space-lg">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-sm">
                    <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                    </div>
                    <div>
                        <span class="font-headline-sm text-headline-sm font-extrabold text-on-surface">رحلة المستوى ونقاط الخبرة</span>
                        <span class="block font-body-sm text-body-sm text-on-surface-variant">نحو لقب بطل المعرفة</span>
                    </div>
                </div>
                <div class="flex items-center gap-space-xs self-start md:self-auto bg-surface-container-high px-space-md py-space-xs rounded-full">
                    <span class="material-symbols-outlined text-primary text-xl">stars</span>
                    <span class="font-label-lg text-label-lg font-bold text-on-surface">{{ $xpTowardsNextLevel }} / 100 XP للمستوى التالي</span>
                </div>
            </div>
            <div class="bg-surface-container-low rounded-lg p-space-md md:p-space-lg">
                <div class="flex items-center justify-between text-on-surface mb-4">
                    <span class="px-space-sm py-1 rounded-full bg-primary text-on-primary font-label-sm text-label-sm font-extrabold">المستوى {{ $level }}</span>
                    <span class="font-headline-sm text-headline-sm font-bold text-primary">المستوى {{ $level + 1 }}</span>
                </div>
                <div class="w-full h-6 rounded-full bg-surface-container-highest overflow-hidden shadow-inner">
                    <div class="h-full rounded-full bg-gradient-to-l from-primary-container via-primary-fixed-dim to-primary transition-all duration-700" style="width: {{ min(100, $progressPercent) }}%"></div>
                </div>
                <p class="mt-3 font-body-md text-body-md text-on-surface">
                    يتبقّى لكِ <strong class="text-primary font-extrabold">{{ $xpToNext }} XP</strong> للوصول إلى المستوى التالي!
                </p>
            </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm text-center">
                <p class="font-label-sm text-label-sm font-bold text-secondary mb-2">إجمالي XP</p>
                <p class="font-headline-md text-headline-md font-extrabold text-on-surface">{{ number_format($student->total_xp) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm text-center">
                <p class="font-label-sm text-label-sm font-bold text-primary mb-2">دقة الأداء</p>
                <p class="font-headline-md text-headline-md font-extrabold text-on-surface">{{ $accuracyPercent }}%</p>
            </div>
            <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm text-center">
                <p class="font-label-sm text-label-sm font-bold text-primary-container mb-2">سلسلة المواظبة</p>
                <p class="font-headline-md text-headline-md font-extrabold text-on-surface">🔥 {{ $streakDays }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm text-center">
                <p class="font-label-sm text-label-sm font-bold text-tertiary mb-2">أوسمة مفتوحة</p>
                <p class="font-headline-md text-headline-md font-extrabold text-on-surface">{{ $badges->where('unlocked', true)->count() }}</p>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
            <div class="flex items-center gap-space-xs mb-space-md">
                <span class="material-symbols-outlined text-primary text-2xl">task_alt</span>
                <h2 class="font-headline-sm text-headline-sm font-extrabold text-on-surface">آخر الإنجازات</h2>
            </div>
            <div class="space-y-3">
                @forelse ($recentAttempts as $attempt)
                    <div class="bg-surface-container-low rounded-lg p-space-md flex items-center justify-between gap-4">
                        <div class="flex items-center gap-space-md min-w-0">
                            <div class="w-12 h-12 rounded-full bg-secondary-container/60 text-secondary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-2xl">auto_stories</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-headline-sm text-headline-sm font-bold text-on-surface block truncate">{{ $attempt->activity?->title ?? 'نشاط' }}</span>
                                <span class="font-body-sm text-body-sm text-on-surface-variant">{{ optional($attempt->completed_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="text-left shrink-0">
                            <span class="font-headline-sm text-headline-sm font-extrabold text-secondary">{{ $attempt->score }}/{{ $attempt->total_questions }}</span>
                        </div>
                    </div>
                @empty
                    <p class="font-body-md text-body-md text-on-surface-variant">لا توجد محاولات بعد — ابدأ نشاطاً من خريطة المغامرة!</p>
                @endforelse
            </div>
        </section>
    </div>
</x-student-layout>
