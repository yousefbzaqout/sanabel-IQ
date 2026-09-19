<x-student-layout>
    <div class="w-full max-w-7xl mx-auto px-gutter py-space-md overflow-hidden pt-28 md:pt-36" dir="rtl">
        <div class="absolute -top-16 -right-16 w-96 h-96 bg-primary-fixed/30 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <section class="flex flex-col gap-space-lg mb-space-xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <div class="inline-flex items-center gap-space-xs self-start px-space-md py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-md text-label-md shadow-sm">
                        <span class="material-symbols-outlined text-base">stars</span>
                        <span>تمارين الذكاء التفاعلية اليومية</span>
                    </div>
                    <h1 class="font-headline-xl text-headline-xl text-on-surface tracking-tight font-extrabold">
                        أنشطة اليوم الممتعة 🎮
                    </h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                        اختر تمرينك المفضل واكسب نقاط النجوم مع صديقك الذكي سُنبل!
                    </p>
                </div>
                <div class="flex items-center gap-space-md p-space-md bg-surface-container-lowest rounded-lg shadow-sm self-start md:self-auto">
                    <img alt="سنبل" class="w-14 h-14 object-cover rounded-full drop-shadow-md" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-secondary font-extrabold">سُنبل جاهز لمساعدتك!</span>
                        <span class="font-headline-sm text-headline-sm text-on-surface font-black">{{ $student->name }} • {{ number_format($student->total_xp) }} XP</span>
                    </div>
                </div>
            </div>
        </section>

        @if ($activities->isEmpty())
            <div class="bg-surface-container-lowest rounded-3xl p-space-xl text-center shadow-md relative overflow-hidden mb-space-xl">
                <div class="absolute w-96 h-96 -top-24 rounded-full bg-gradient-to-b from-primary-container/15 via-secondary-container/10 to-transparent blur-3xl pointer-events-none"></div>
                <div class="inline-flex items-center gap-1.5 px-space-md py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-md text-label-md font-bold mb-space-md">
                    <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                    بداية رحلة التعلّم الممتعة
                </div>
                <img alt="سنبل" class="w-40 h-40 mx-auto object-cover rounded-full drop-shadow-xl mb-4" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                <h2 class="font-headline-md text-headline-md text-on-surface font-extrabold mb-space-xs">
                    لا توجد أنشطة منشورة حالياً لـ {{ $student->name }}
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mx-auto">
                    لم تُسند أنشطة بعد. عد لاحقاً أو اطلب من ولي الأمر إضافة مواد من بوابة الكبار.
                </p>
                <a href="{{ route('student.dashboard') }}" class="mt-space-lg inline-flex items-center gap-2 px-space-lg py-3.5 rounded-full bg-primary-container text-on-primary-fixed font-label-lg text-label-lg font-bold shadow-[0_4px_0_#d97706]">
                    <span class="material-symbols-outlined">map</span>
                    العودة لخريطة المغامرة
                </a>
            </div>
        @else
            <section class="mb-space-xl">
                <div class="flex items-center justify-between mb-space-md">
                    <div class="flex items-center gap-space-xs">
                        <span class="material-symbols-outlined text-secondary text-2xl">grid_view</span>
                        <h2 class="font-headline-md text-headline-md text-on-surface font-extrabold">تمارين اليوم التفاعلية</h2>
                    </div>
                    <span class="font-label-md text-label-md text-on-surface-variant font-bold">{{ $activities->count() }} متاحة</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-space-lg">
                    @foreach ($activities as $activity)
                        @php
                            $questionCount = count($activity->payload['questions'] ?? []);
                            $latestAttempt = $activity->attempts->first();
                            $tones = [
                                ['chip' => 'bg-secondary-container/40 text-on-secondary-container', 'btn' => 'bg-secondary text-on-secondary shadow-[0_4px_0_#005049]', 'icon' => 'mic'],
                                ['chip' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant', 'btn' => 'bg-tertiary text-on-tertiary shadow-[0_4px_0_#2b29bb]', 'icon' => 'draw'],
                                ['chip' => 'bg-primary-fixed text-on-primary-fixed-variant', 'btn' => 'bg-primary-container text-on-primary-container shadow-[0_4px_0_#b45309]', 'icon' => 'pin'],
                                ['chip' => 'bg-secondary-fixed text-on-secondary-fixed-variant', 'btn' => 'bg-secondary text-on-secondary shadow-[0_4px_0_#005049]', 'icon' => 'style'],
                            ];
                            $tone = $tones[$loop->index % count($tones)];
                        @endphp
                        <div class="group bg-surface-container-lowest rounded-lg p-space-lg shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex flex-col gap-space-md">
                                <div class="flex items-center justify-between">
                                    <span class="px-space-md py-1 rounded-full {{ $tone['chip'] }} font-label-md text-label-md font-extrabold inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-base">{{ $tone['icon'] }}</span>
                                        نشاط تفاعلي
                                    </span>
                                    <span class="font-label-md text-label-md font-bold text-on-surface-variant">{{ $activity->xp_reward }} XP</span>
                                </div>
                                <h3 class="font-headline-sm text-headline-sm text-on-surface font-black group-hover:text-primary transition-colors">
                                    {{ $activity->title }}
                                </h3>
                                @if ($activity->parentMaterial)
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $activity->parentMaterial->title }}</p>
                                @endif
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-surface-container-low px-3 py-1 text-label-sm font-bold text-on-surface">{{ $questionCount }} أسئلة</span>
                                    @if ($latestAttempt)
                                        <span class="rounded-full bg-secondary-container/50 px-3 py-1 text-label-sm font-bold text-on-secondary-container">
                                            مكتمل {{ $latestAttempt->score }}/{{ $latestAttempt->total_questions }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-space-lg pt-space-sm flex justify-end">
                                <a href="{{ route('student.activities.play', $activity) }}" class="px-space-lg py-space-sm rounded-full {{ $tone['btn'] }} font-label-lg text-label-lg font-bold hover:brightness-110 active:translate-y-1 active:shadow-none transition-all inline-flex items-center gap-1">
                                    <span>{{ $latestAttempt ? 'إعادة اللعب' : 'ابدأ الآن' }}</span>
                                    <span class="material-symbols-outlined">rocket_launch</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-student-layout>
