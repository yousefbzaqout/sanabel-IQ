<x-student-layout>
    <div class="max-w-7xl mx-auto px-gutter w-full space-y-space-xl pb-space-xl pt-28 md:pt-36" dir="rtl">
        <section class="relative rounded-lg bg-gradient-to-l from-primary-fixed/70 via-surface-container to-secondary-container/50 p-space-lg md:p-space-xl shadow-md overflow-hidden">
            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-space-lg">
                <div class="space-y-space-sm max-w-2xl text-center lg:text-right">
                    <div class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-lowest/80 backdrop-blur-md text-primary font-bold shadow-sm">
                        <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">stars</span>
                        <span class="font-label-md text-label-md">متحف الإنجازات والبطولات</span>
                    </div>
                    <h1 class="font-headline-xl text-headline-xl font-extrabold text-on-surface tracking-tight leading-tight">
                        خزانة أوسمتك وجوائزك الذهبية 🏆
                    </h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                        كل تحدٍ تنجزه يمنحك وساماً لامعاً! السلسلة الحالية:
                        <strong class="text-primary">{{ $currentStreak }} يوماً</strong>
                        (أفضل سلسلة: {{ $maxStreak }})
                    </p>
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-space-sm pt-space-xs">
                        <div class="flex items-center gap-space-xs px-space-md py-space-sm rounded-full bg-surface-container-lowest text-on-surface shadow-sm">
                            <span class="material-symbols-outlined text-primary-container text-xl" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                            <span class="font-label-lg text-label-lg font-extrabold">
                                الأوسمة: {{ $badges->where('unlocked', true)->count() }} من {{ $badges->count() }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 relative">
                    <div class="bg-surface-container-lowest p-space-md rounded-lg shadow-md flex flex-col items-center border-b-4 border-primary-fixed">
                        <img class="w-32 h-32 object-cover rounded-full" alt="سنبل" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                        <span class="font-label-sm text-label-sm bg-primary-fixed text-on-primary-fixed-variant px-space-sm py-0.5 rounded-full font-bold mt-2">صديقك سنبل</span>
                        <p class="font-label-md text-label-md text-on-surface font-black mt-1">«فخور بكِ يا {{ $student->name }}!»</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-space-md">
            <div class="flex items-center gap-space-xs">
                <div class="w-10 h-10 rounded-full bg-secondary-container/40 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                </div>
                <div>
                    <h3 class="font-headline-md text-headline-md font-extrabold text-on-surface">الأوسمة المكتسبة</h3>
                    <span class="font-body-sm text-body-sm text-secondary font-bold">أوسمة براقة تم فتحها بجهدك!</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-space-lg">
                @foreach ($badges->where('unlocked', true) as $entry)
                    <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-md border-b-4 border-primary-container hover:-translate-y-1.5 transition-all flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-space-sm mb-space-md">
                            <div class="w-16 h-16 rounded-full bg-primary-fixed flex items-center justify-center text-primary-container shadow-md">
                                <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">hotel_class</span>
                            </div>
                            <span class="px-space-sm py-1 rounded-full bg-secondary-container/40 text-on-secondary-container font-label-sm text-label-sm font-black inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">verified</span> مكتسب
                            </span>
                        </div>
                        <div>
                            <h4 class="font-headline-sm text-headline-sm font-extrabold text-on-surface mb-1">{{ $entry['badge']->name_ar }}</h4>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mb-space-md">{{ $entry['badge']->description_ar }}</p>
                        </div>
                        <div class="pt-space-sm bg-surface-container-low/50 rounded p-space-sm text-label-sm text-on-surface-variant">
                            تاريخ الاكتساب:
                            <strong class="text-on-surface">{{ optional($entry['unlocked_at'])->format('Y-m-d') ?? '—' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-space-md">
            <div class="flex items-center gap-space-xs">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-2xl">lock</span>
                </div>
                <div>
                    <h3 class="font-headline-md text-headline-md font-extrabold text-on-surface">أوسمة قيد التحدي</h3>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">اقتربتِ جداً من فتح هذه الأوسمة!</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-lg">
                @foreach ($badges->where('unlocked', false) as $entry)
                    <div class="bg-surface-container-lowest/80 rounded-lg p-space-lg shadow-sm border-b-4 border-surface-container-high flex flex-col justify-between relative overflow-hidden">
                        <div class="flex items-start justify-between gap-space-sm mb-space-sm">
                            <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant relative">
                                <span class="material-symbols-outlined text-4xl text-primary-fixed-dim">workspace_premium</span>
                                <div class="absolute inset-0 bg-surface-container/60 backdrop-blur-[2px] rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-xl text-outline">lock</span>
                                </div>
                            </div>
                            <span class="px-space-sm py-1 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-bold">مقفل</span>
                        </div>
                        <div>
                            <h4 class="font-headline-sm text-headline-sm font-extrabold text-on-surface mb-1">{{ $entry['badge']->name_ar }}</h4>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $entry['badge']->description_ar }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm border-b-4 border-secondary flex flex-col sm:flex-row items-center justify-between gap-space-md text-center sm:text-right">
            <div class="flex items-center gap-space-md">
                <div class="w-14 h-14 rounded-full bg-secondary-container/50 flex-shrink-0 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
                </div>
                <div>
                    <h4 class="font-headline-sm text-headline-sm font-black text-on-surface">مستعد لفتح وسام جديد الآن؟</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">تابع مغامراتك في خريطة الدروس!</p>
                </div>
            </div>
            <a href="{{ route('student.dashboard') }}" class="px-space-xl py-space-sm rounded-full bg-primary-container text-on-primary-container font-headline-sm text-headline-sm font-extrabold shadow-md hover:scale-105 transition-transform inline-flex items-center gap-space-xs flex-shrink-0">
                <span class="material-symbols-outlined">play_arrow</span>
                <span>متابعة رحلة التعلم 🚀</span>
            </a>
        </section>
    </div>
</x-student-layout>
