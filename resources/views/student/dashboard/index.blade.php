<x-student-layout>
    <div class="flex flex-col w-full px-gutter py-space-md max-w-7xl mx-auto space-y-space-lg select-none pt-28 md:pt-36" dir="rtl">
        {{-- Welcome banner --}}
        <section class="relative overflow-hidden rounded-xl bg-gradient-to-l from-primary-fixed via-primary-fixed-dim/40 to-surface-container-low p-space-md md:p-space-lg shadow-[0_12px_36px_-6px_rgba(245,158,11,0.22)]">
            <div class="absolute -top-16 -right-16 w-52 h-52 bg-primary-container/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-md flex-1">
                    <div class="relative shrink-0">
                        <div class="absolute inset-0 rounded-full bg-primary-container/30 blur-md animate-pulse"></div>
                        <img alt="سنبل" class="relative z-10 w-24 h-24 md:w-32 md:h-32 object-cover rounded-full drop-shadow-xl" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                        <span class="absolute -bottom-1 -right-1 bg-surface-container-lowest px-2 py-0.5 rounded-full text-label-sm font-bold text-on-primary-fixed-variant shadow-md">سنبل معك 🐥</span>
                    </div>
                    <div class="space-y-space-xs">
                        <div class="relative bg-surface-container-lowest text-on-surface p-space-md rounded-2xl md:rounded-3xl shadow-sm border-2 border-primary-container/20">
                            <p class="font-headline-sm text-headline-sm md:text-headline-md text-on-primary-fixed leading-tight font-extrabold">
                                أهلاً بك يا بطل {{ $student->name }}! 🌟 جاهز لمغامرة اليوم؟
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                                أكمل محطة واحدة اليوم للمحافظة على شعلتك النشطة
                                <span class="text-primary font-bold">🔥 ({{ $streakDays }} يوماً)</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-auto shrink-0 bg-surface-container-lowest/80 backdrop-blur-md p-space-md rounded-2xl shadow-sm flex items-center justify-around gap-space-md">
                    <div class="text-center px-2">
                        <div class="font-label-sm text-label-sm text-on-surface-variant">الترتيب الأسبوعي</div>
                        <div class="font-headline-sm text-headline-sm text-primary font-extrabold">#{{ $weeklyRank }}</div>
                    </div>
                    <div class="h-10 w-px bg-outline-variant/30"></div>
                    <div class="text-center px-2">
                        <div class="font-label-sm text-label-sm text-on-surface-variant">نقاطك</div>
                        <div class="font-headline-sm text-headline-sm text-secondary font-extrabold">{{ number_format($student->total_xp) }} XP</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
            <section class="lg:col-span-8 bg-surface-container-lowest rounded-xl p-space-md md:p-space-lg shadow-sm relative overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-space-sm pb-space-md border-b border-surface-container-low mb-space-md">
                    <div class="flex items-center gap-space-xs">
                        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary-container">
                            <span class="material-symbols-outlined text-2xl">explore</span>
                        </div>
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface font-extrabold">خريطة المغامرة السحرية</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">سر في الدرب، واجمع النجوم وافتح القلعة الكبرى!</p>
                        </div>
                    </div>
                </div>

                <x-student.learning-map :student="$student" />
            </section>

            <aside class="lg:col-span-4 space-y-space-md">
                <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm space-y-space-md">
                    <div class="flex items-center justify-between pb-2 border-b border-surface-container-low">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary-container">
                                <span class="material-symbols-outlined text-xl">bolt</span>
                            </div>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-extrabold">اختصارات اليوم ⚡</h3>
                        </div>
                    </div>
                    <a href="{{ route('student.activities.index') }}" class="block p-space-sm rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors">
                        <h4 class="font-label-lg text-label-lg font-bold text-on-surface">الأنشطة اليومية</h4>
                        <p class="font-body-sm text-label-md text-on-surface-variant">ألعاب صوت ورسم وذكاء</p>
                    </a>
                    <a href="{{ route('student.progress') }}" class="block p-space-sm rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors">
                        <h4 class="font-label-lg text-label-lg font-bold text-on-surface">مسار تقدمي</h4>
                        <p class="font-body-sm text-label-md text-on-surface-variant">XP والمستويات والإنجازات</p>
                    </a>
                    <a href="{{ route('student.badges') }}" class="block p-space-sm rounded-2xl bg-gradient-to-br from-primary-fixed/40 to-primary-container/20 border border-primary-container/30">
                        <h4 class="font-label-lg text-label-lg font-bold text-on-surface">خزانة الجوائز 🎁</h4>
                        <p class="font-body-sm text-label-sm text-on-surface-variant">{{ $badges->count() }} أوسمة حديثة</p>
                    </a>
                </div>

                @if ($materials->isNotEmpty())
                    <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm space-y-2">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-extrabold mb-2">دروس جاهزة</h3>
                        @foreach ($materials->take(3) as $material)
                            <a href="{{ $material->studentLaunchUrl() }}" class="flex items-center justify-between rounded-2xl bg-surface-container-low px-3 py-2 hover:bg-surface-container transition-colors">
                                <span class="font-label-md text-label-md font-bold text-on-surface truncate">{{ $material->title }}</span>
                                <span class="text-label-sm font-bold text-primary shrink-0">{{ $material->xp_reward }} XP</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-student-layout>
