@php
    $accuracy = (int) ($analysis['overall_accuracy_percent'] ?? 0);
    $lessonPct = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;
    $remainingLessons = max(0, $totalLessons - $completedLessons);
    $level = $student !== null ? max(1, (int) floor($student->total_xp / 100) + 1) : 1;
    $barTone = [
        'primary' => 'bg-primary-container',
        'secondary' => 'bg-secondary',
        'tertiary' => 'bg-tertiary',
    ];
@endphp

<x-filament-widgets::widget>
    <div class="fi-wi-stitch-parent-dashboard flex flex-col w-full gap-space-lg font-body-md text-on-surface" dir="rtl">
        @if ($student === null)
            <section class="flex flex-col items-center gap-space-md bg-surface-container-lowest p-space-xl rounded-lg shadow-sm text-center">
                <span class="material-symbols-outlined text-4xl text-primary-container">family_restroom</span>
                <h2 class="font-headline-md text-headline-md text-on-surface font-extrabold">أضف طفلاً للبدء</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">فعّل ملف ابنك لمتابعة الإتقان والأوسمة والواجبات.</p>
                <a
                    href="{{ \App\Filament\Parent\Resources\Children\ChildResource::getUrl('create') }}"
                    class="inline-flex items-center gap-2 bg-primary-container hover:brightness-105 text-on-primary-container px-5 py-2.5 rounded-full font-label-lg text-label-lg font-bold shadow-sm"
                >
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    إضافة طفل جديد
                </a>
            </section>
        @else
            {{-- TOP CONTEXT --}}
            <section class="flex flex-col gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-space-md">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-space-sm flex-wrap">
                            <h1 class="font-headline-md text-headline-md text-on-surface font-extrabold tracking-tight">
                                لوحة متابعة الطفل: {{ $student->name }}
                            </h1>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-md text-label-md font-bold">
                                <span class="material-symbols-outlined text-base text-primary-container" style="font-variation-settings: 'FILL' 1;">star</span>
                                المستوى {{ $level }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap font-body-sm text-body-sm text-on-surface-variant">
                            <span class="flex items-center gap-1 font-semibold text-on-surface">
                                <span class="material-symbols-outlined text-secondary text-base">school</span>
                                {{ $student->tenant?->name ?? 'مدرسة مرتبطة' }}
                            </span>
                            <span class="text-outline-variant">•</span>
                            <span>الصف {{ $student->grade_level }}</span>
                            <span class="text-outline-variant">•</span>
                            <span>{{ $student->school_term === 2 ? 'الفصل الدراسي الثاني' : 'الفصل الدراسي الأول' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-space-sm">
                        <div class="relative group">
                            <button class="flex items-center gap-2.5 bg-surface-container-low hover:bg-surface-container text-on-surface px-4 py-2 rounded-full font-label-lg text-label-lg transition-all shadow-sm" type="button">
                                <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                                <span>{{ $student->name }}</span>
                                <span class="material-symbols-outlined text-on-surface-variant text-lg">arrow_drop_down</span>
                            </button>
                            <div class="absolute left-0 mt-2 w-52 bg-surface-container-lowest rounded-xl shadow-lg p-1.5 hidden group-hover:block z-50">
                                <div class="px-3 py-2 text-label-sm font-label-sm text-on-surface-variant font-bold">التبديل بين الأبناء</div>
                                @foreach ($siblings as $sibling)
                                    <form method="POST" action="{{ route('students.select', $sibling) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center justify-between px-3 py-2 rounded font-label-md text-label-md transition-colors {{ $sibling->id === $student->id ? 'bg-surface-container-low text-on-surface font-bold' : 'text-on-surface-variant hover:bg-surface-container' }}"
                                        >
                                            <span>{{ $sibling->name }}</span>
                                            @if ($sibling->id === $student->id)
                                                <span class="material-symbols-outlined text-secondary text-sm">check_circle</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>

                        <a
                            href="{{ route('parent.students.export', ['student' => $student, 'format' => 'pdf']) }}"
                            target="_blank"
                            class="flex items-center gap-1.5 bg-primary-container hover:brightness-105 active:translate-y-0.5 text-on-primary-container px-4 py-2 rounded-full font-label-lg text-label-lg font-bold shadow-sm transition-all"
                        >
                            <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                            <span>تصدير تقرير أسبوعي PDF</span>
                        </a>
                        <a
                            href="{{ route('parent.students.export', ['student' => $student, 'format' => 'csv']) }}"
                            target="_blank"
                            class="flex items-center gap-1.5 bg-surface-container-lowest hover:bg-surface-container-low text-secondary px-3.5 py-2 rounded-full font-label-lg text-label-lg font-bold shadow-sm transition-colors border border-secondary/20"
                        >
                            <span class="material-symbols-outlined text-lg">table_chart</span>
                            <span>تقرير درجات CSV</span>
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm text-on-surface-variant">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-secondary-fixed-dim animate-pulse"></span>
                        <span>آخر مزامنة مع منصة المدرسة: {{ now()->translatedFormat('l، h:i a') }}</span>
                    </div>
                    <span class="text-secondary font-bold">النظام متصل ومحدّث لحظياً</span>
                </div>
            </section>

            {{-- KPI STATS --}}
            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
                <div class="bg-surface-container-lowest rounded-lg p-space-md flex flex-col justify-between shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold">إجمالي النقاط والخبرة (Total XP)</span>
                        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-bold">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                        </div>
                    </div>
                    <div class="mt-space-md flex flex-col gap-1">
                        <span class="font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">
                            {{ number_format($student->total_xp) }}
                            <span class="font-label-lg text-label-lg font-semibold text-primary">XP</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center text-secondary font-label-md text-label-md font-bold">
                                <span class="material-symbols-outlined text-sm">trending_up</span>
                                +{{ number_format($weeklyXp) }} XP
                            </span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">مكتسبة هذا الأسبوع</span>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -left-6 w-20 h-20 rounded-full bg-primary-fixed/20 pointer-events-none"></div>
                </div>

                <div class="bg-surface-container-lowest rounded-lg p-space-md flex flex-col justify-between shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold">نسبة دقة الإجابات (Accuracy)</span>
                        <div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center text-secondary font-bold">
                            <span class="material-symbols-outlined text-xl">verified</span>
                        </div>
                    </div>
                    <div class="mt-space-md flex flex-col gap-1">
                        <div class="flex items-baseline gap-2">
                            <span class="font-headline-lg text-headline-lg text-on-surface font-extrabold">{{ $accuracy }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
                                <div class="bg-secondary h-2 rounded-full" style="width: {{ min(100, $accuracy) }}%"></div>
                            </div>
                            <span class="font-label-sm text-label-sm text-on-surface-variant whitespace-nowrap">تقييم صوتي</span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-lg p-space-md flex flex-col justify-between shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold">سلسلة المواظبة (Daily Streak)</span>
                        <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center text-error font-bold">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                        </div>
                    </div>
                    <div class="mt-space-md flex flex-col gap-1">
                        <span class="font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">
                            {{ $streakDays }}
                            <span class="font-body-md text-body-md text-on-surface-variant font-semibold">يوماً متتالياً</span>
                        </span>
                        <div class="flex items-center gap-1.5 font-label-sm text-label-sm text-error font-bold">
                            <span class="material-symbols-outlined text-sm">workspace_premium</span>
                            <span>شعلة نشطة</span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-lg p-space-md flex flex-col justify-between shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold">التحديات والدروس المكتملة</span>
                        <div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center text-tertiary font-bold">
                            <span class="material-symbols-outlined text-xl">auto_stories</span>
                        </div>
                    </div>
                    <div class="mt-space-md flex flex-col gap-1">
                        <span class="font-headline-lg text-headline-lg text-on-surface font-extrabold tracking-tight">
                            {{ $completedLessons }}
                            <span class="font-body-md text-body-md text-on-surface-variant">/ {{ $totalLessons }} درساً</span>
                        </span>
                        <div class="flex items-center justify-between font-label-sm text-label-sm text-on-surface-variant">
                            <span class="font-semibold text-tertiary">نسبة إنجاز {{ $lessonPct }}%</span>
                            <span>متبقٍ {{ $remainingLessons }} {{ $remainingLessons === 1 ? 'درس فقط' : 'درساً' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- MAIN GRID --}}
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
                <div class="lg:col-span-8 flex flex-col gap-space-lg">
                    <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm flex flex-col gap-space-md">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm">
                            <div class="flex flex-col gap-1">
                                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">
                                    مؤشر إتقان المهارات والمواد الدراسية (Mastery Analytics)
                                </h2>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    تحليل أدائي دقيق مدعوم بنموذج الذكاء الاصطناعي لكل مجال تعليمي
                                </p>
                            </div>
                            <div class="flex items-center bg-surface-container-low p-1 rounded-full self-start">
                                <button
                                    type="button"
                                    wire:click="setChartPeriod('7')"
                                    class="px-3.5 py-1 rounded-full font-label-sm text-label-sm transition-all {{ ($chartPeriod ?? '7') === '7' ? 'bg-primary-container text-on-primary-container font-bold shadow-sm' : 'text-on-surface-variant font-semibold hover:text-on-surface' }}"
                                >آخر 7 أيام</button>
                                <button
                                    type="button"
                                    wire:click="setChartPeriod('30')"
                                    class="px-3.5 py-1 rounded-full font-label-sm text-label-sm transition-all {{ ($chartPeriod ?? '7') === '30' ? 'bg-primary-container text-on-primary-container font-bold shadow-sm' : 'text-on-surface-variant font-semibold hover:text-on-surface' }}"
                                >آخر 30 يوماً</button>
                                <button
                                    type="button"
                                    wire:click="setChartPeriod('semester')"
                                    class="px-3.5 py-1 rounded-full font-label-sm text-label-sm transition-all {{ ($chartPeriod ?? '7') === 'semester' ? 'bg-primary-container text-on-primary-container font-bold shadow-sm' : 'text-on-surface-variant font-semibold hover:text-on-surface' }}"
                                >الفصل كامل</button>
                            </div>
                        </div>

                        @php
                            $trend = $masteryTrend ?? ['has_data' => false, 'paths' => []];
                            $paths = $trend['paths'] ?? [];
                            $chartState = ($trend['has_data'] ?? false) ? 'ready' : 'empty';
                        @endphp
                        <div class="w-full bg-surface-container-low/60 rounded-xl p-space-md flex flex-col gap-space-md" data-mastery-chart="{{ $chartState }}">
                            <div class="w-full h-64 relative flex items-end">
                                @if ($chartState === 'empty')
                                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center px-4">
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant">show_chart</span>
                                        <p class="font-label-lg text-label-lg font-bold text-on-surface">لا توجد بيانات إتقان بعد</p>
                                        <p class="font-body-sm text-body-sm text-on-surface-variant">سيظهر المنحنى هنا فور إكمال {{ $student->name }} أول نشاط أو اختبار.</p>
                                    </div>
                                @endif
                                <svg class="w-full h-full {{ $chartState === 'empty' ? 'opacity-30' : '' }}" fill="none" preserveAspectRatio="none" viewBox="0 0 680 220" role="img" aria-label="منحنى إتقان المهارات">
                                    <line stroke="#dae2fd" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="680" y1="20" y2="20"></line>
                                    <line stroke="#dae2fd" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="680" y1="70" y2="70"></line>
                                    <line stroke="#dae2fd" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="680" y1="120" y2="120"></line>
                                    <line stroke="#dae2fd" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="680" y1="170" y2="170"></line>
                                    <defs>
                                        <linearGradient id="stitchChartAmber" x1="0%" x2="0%" y1="0%" y2="100%">
                                            <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.25"></stop>
                                            <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"></stop>
                                        </linearGradient>
                                        <linearGradient id="stitchChartTeal" x1="0%" x2="0%" y1="0%" y2="100%">
                                            <stop offset="0%" stop-color="#006a61" stop-opacity="0.2"></stop>
                                            <stop offset="100%" stop-color="#006a61" stop-opacity="0"></stop>
                                        </linearGradient>
                                    </defs>
                                    @if ($chartState === 'ready')
                                        @if (! empty($paths['amber_area']))
                                            <path d="{{ $paths['amber_area'] }}" fill="url(#stitchChartAmber)"></path>
                                        @endif
                                        @if (! empty($paths['amber_stroke']))
                                            <path d="{{ $paths['amber_stroke'] }}" stroke="#f59e0b" stroke-linecap="round" stroke-width="3.5" fill="none"></path>
                                        @endif
                                        @if (! empty($paths['teal_area']))
                                            <path d="{{ $paths['teal_area'] }}" fill="url(#stitchChartTeal)"></path>
                                        @endif
                                        @if (! empty($paths['teal_stroke']))
                                            <path d="{{ $paths['teal_stroke'] }}" stroke="#006a61" stroke-linecap="round" stroke-width="2.5" fill="none"></path>
                                        @endif
                                    @endif
                                </svg>
                            </div>
                            @if ($chartState === 'ready')
                                <div class="flex flex-wrap items-center gap-3 font-label-sm text-label-sm text-on-surface-variant">
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-primary-container"></span>إتقان تراكمي</span>
                                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-secondary"></span>دقة اليوم</span>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-sm">
                                @foreach ($skills as $skill)
                                    <div class="flex flex-col gap-1.5 bg-surface-container-lowest rounded-xl p-3 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="font-label-md text-label-md text-on-surface font-bold">{{ $skill['label'] }}</span>
                                            <span class="font-label-md text-label-md text-primary font-extrabold">{{ $skill['percent'] }}%</span>
                                        </div>
                                        <div class="w-full h-2 rounded-full bg-surface-container-high overflow-hidden">
                                            <div class="h-full rounded-full {{ $barTone[$skill['tone']] ?? 'bg-primary-container' }}" style="width: {{ min(100, $skill['percent']) }}%"></div>
                                        </div>
                                        <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $skill['hint'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm flex flex-col gap-space-md">
                        <div class="flex items-center justify-between gap-space-sm">
                            <div class="flex items-center gap-space-sm">
                                <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined text-lg">leaderboard</span>
                                </div>
                                <div>
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">لوحة المتصدرين وتحديات الأسبوع</h2>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">الصف {{ $student->grade_level }} • ترتيب الأسبوع</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-surface-container-low px-3 py-1 font-label-sm text-label-sm font-bold text-on-surface-variant">مرتبة #{{ $rank ?? '—' }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right">
                                <thead>
                                    <tr class="bg-surface-container-low font-label-sm text-label-sm text-on-surface-variant font-bold">
                                        <th class="rounded-r-lg px-4 py-3">الترتيب</th>
                                        <th class="px-4 py-3">الطالب</th>
                                        <th class="rounded-l-lg px-4 py-3">نقاط الأسبوع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($leaders as $index => $leader)
                                        @php $isChild = (int) $leader->id === (int) $student->id; @endphp
                                        <tr class="{{ $isChild ? 'bg-primary-fixed/30' : 'hover:bg-surface-container-low/60' }}">
                                            <td class="px-4 py-3.5 font-bold text-on-surface">
                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $index === 0 ? 'bg-primary-fixed text-primary' : 'bg-surface-container' }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold {{ $isChild ? 'text-primary' : 'text-on-surface' }}">
                                                        {{ $leader->name }}
                                                    </span>
                                                    @if ($isChild)
                                                        <span class="rounded-full bg-primary-container px-2 py-0.5 font-label-sm text-label-sm font-extrabold text-on-primary-container">طفلك</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 font-bold {{ $isChild ? 'text-primary' : 'text-on-surface' }}">
                                                {{ number_format((int) ($leader->weekly_xp ?? $leader->total_xp)) }} XP
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-on-surface-variant">لا توجد بيانات ترتيب بعد.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 flex flex-col gap-space-lg">
                    <div class="bg-surface-container-lowest rounded-lg p-space-lg shadow-sm flex flex-col gap-space-md">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-16 h-16 shrink-0 rounded-full bg-primary-fixed p-1 shadow-sm overflow-hidden flex items-center justify-center">
                                <img alt="سنبل" class="h-full w-full object-cover rounded-full" src="{{ asset('brand/mascot-sanbal.jpg') }}">
                            </div>
                            <div>
                                <span class="block font-headline-sm text-headline-sm text-on-surface font-bold">توجيهات سنبل الذكية</span>
                                <span class="font-label-sm text-label-sm font-bold text-secondary">تحليل أسبوعي</span>
                            </div>
                        </div>

                        <div class="bg-surface-container-low rounded-xl p-space-md">
                            <div class="mb-2 inline-flex items-center gap-1.5 font-label-md text-label-md font-bold text-primary">
                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                                ملاحظة سنبل:
                            </div>
                            <p class="font-body-sm text-body-sm leading-relaxed text-on-surface">
                                @if ($accuracy >= 85)
                                    "{{ $student->name }} يتألق هذا الأسبوع بدقة {{ $accuracy }}%! عزّز الثقة بجلسة قصيرة من الثناء والمتابعة."
                                @elseif ($accuracy > 0)
                                    "{{ $student->name }} يسير بثبات (دقة {{ $accuracy }}%). نقترح تمريناً يومياً لمدة 10 دقائق على نقاط الضعف."
                                @else
                                    "ابدأ مع {{ $student->name }} أول محطة اليوم — سنبل جاهز للتوجيه خطوة بخطوة."
                                @endif
                            </p>
                        </div>

                        <a
                            href="{{ \App\Filament\Parent\Resources\Goals\GoalResource::getUrl() }}"
                            class="flex w-full items-center justify-center gap-2 bg-primary-container hover:brightness-105 text-on-primary-container py-3 rounded-full font-label-lg text-label-lg font-extrabold shadow-sm transition-all"
                        >
                            <span class="material-symbols-outlined text-xl">rocket_launch</span>
                            ابدأ تمريناً مقترحاً مع {{ $student->name }}
                        </a>
                    </div>

                    <div class="bg-primary-fixed/40 rounded-lg p-space-lg shadow-sm flex flex-col gap-space-sm">
                        <div class="flex items-center gap-2 text-primary">
                            <span class="material-symbols-outlined text-xl">psychology</span>
                            <span class="font-headline-sm text-headline-sm font-bold">نصيحة تربوية سريعة</span>
                        </div>
                        <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">
                            التعزيز الإيجابي يعزز ثقة طفلك. امنح {{ $student->name }} دقائق من الثناء الصادق على التقدم المستمر.
                        </p>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-filament-widgets::widget>
