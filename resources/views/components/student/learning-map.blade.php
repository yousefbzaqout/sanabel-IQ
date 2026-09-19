@php
    $nodes = $mapNodes;
    $offsets = ['ms-0', 'ms-8 sm:ms-16', 'ms-0 sm:ms-4', 'ms-10 sm:ms-20', 'ms-2 sm:ms-8'];
@endphp

<section
    data-learning-map
    data-student-id="{{ $student->id }}"
    data-grade-level="{{ $student->grade_level }}"
    class="relative overflow-hidden rounded-3xl border border-emerald-200/70 bg-gradient-to-b from-sky-100 via-emerald-50 to-amber-50 p-6 shadow-sm dark:border-emerald-900 dark:from-slate-900 dark:via-emerald-950/40 dark:to-amber-950/30 sm:p-8"
    aria-label="خريطة مسار التعلم"
>
    {{-- Stylized hills / olive-grove backdrop --}}
    <div class="pointer-events-none absolute inset-0 opacity-40" aria-hidden="true">
        <svg class="absolute bottom-0 start-0 h-40 w-full text-emerald-300 dark:text-emerald-800" viewBox="0 0 800 160" preserveAspectRatio="none">
            <path fill="currentColor" d="M0 120 C120 60 180 140 280 90 C360 50 420 130 520 80 C620 30 700 110 800 70 L800 160 L0 160 Z" />
        </svg>
        <svg class="absolute bottom-0 start-0 h-28 w-full text-lime-200 dark:text-lime-900" viewBox="0 0 800 120" preserveAspectRatio="none">
            <path fill="currentColor" d="M0 90 C100 40 200 110 320 70 C440 30 560 100 680 55 C740 35 780 70 800 50 L800 120 L0 120 Z" />
        </svg>
        <div class="absolute top-6 end-8 h-16 w-16 rounded-full bg-amber-200/70 blur-sm dark:bg-amber-400/20"></div>
    </div>

    <div class="relative z-10 mb-6 flex items-center justify-between gap-3" dir="rtl">
        <div>
            <h3 class="text-xl font-bold text-emerald-950 dark:text-emerald-100">مسار التعلم</h3>
            <p class="text-sm text-emerald-800/80 dark:text-emerald-200/80">
                الصف {{ $student->grade_level }} — اختر المحطة التالية مع سنبل
            </p>
        </div>
        <span class="rounded-full bg-white/80 px-3 py-1 text-sm font-semibold text-emerald-800 shadow-sm dark:bg-emerald-950/70 dark:text-emerald-100">
            {{ count($nodes) }} محطات
        </span>
    </div>

    @if ($nodes === [])
        <p class="relative z-10 rounded-2xl bg-white/70 p-6 text-center text-sm text-gray-600 dark:bg-slate-900/50 dark:text-gray-300">
            لا توجد مواد منشورة لهذا الصف بعد. سنضيف مسار التعلم قريباً!
        </p>
    @else
        <ol class="relative z-10 mx-auto flex max-w-xl flex-col gap-0">
            @foreach ($nodes as $index => $node)
                @php
                    $offset = $offsets[$index % count($offsets)];
                    $isAvailable = $node['state'] === 'available';
                    $isCompleted = $node['state'] === 'completed';
                    $isLocked = $node['state'] === 'locked';
                @endphp

                <li class="relative {{ $offset }}">
                    @if (! $loop->last)
                        <span
                            class="absolute start-7 top-16 h-10 w-1 rounded-full {{ $isCompleted ? 'bg-emerald-400' : 'bg-emerald-200/80 dark:bg-emerald-800' }}"
                            aria-hidden="true"
                        ></span>
                    @endif

                    <div class="relative flex items-start gap-4 py-3">
                        @if ($isLocked)
                            <div
                                data-map-node
                                data-node-state="locked"
                                data-subject-id="{{ $node['subject_id'] }}"
                                class="group flex w-full max-w-sm cursor-not-allowed items-center gap-4 rounded-2xl border border-gray-200 bg-white/60 px-4 py-3 opacity-70 shadow-sm dark:border-gray-700 dark:bg-slate-900/40"
                                aria-disabled="true"
                            >
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-4 border-gray-300 bg-gray-100 text-2xl text-gray-500 dark:border-gray-600 dark:bg-gray-800">
                                    🔒
                                </span>
                                <div class="min-w-0 text-start" dir="auto">
                                    <p class="truncate font-semibold text-gray-500 dark:text-gray-400">{{ $node['title'] }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">مقفل — أكمل المحطة السابقة أولاً</p>
                                </div>
                            </div>
                        @else
                            <a
                                href="{{ $node['url'] }}"
                                data-map-node
                                data-node-state="{{ $node['state'] }}"
                                data-subject-id="{{ $node['subject_id'] }}"
                                @class([
                                    'group relative flex w-full max-w-sm items-center gap-4 rounded-2xl border px-4 py-3 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2',
                                    'border-emerald-400 bg-emerald-50 focus:ring-emerald-400 dark:border-emerald-600 dark:bg-emerald-950/50' => $isCompleted,
                                    'border-amber-400 bg-amber-50 focus:ring-amber-400 dark:border-amber-500 dark:bg-amber-950/40' => $isAvailable,
                                ])
                            >
                                <span
                                    @class([
                                        'relative flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-4 text-2xl',
                                        'border-emerald-500 bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' => $isCompleted,
                                        'border-amber-400 bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-100 ring-4 ring-amber-300/60' => $isAvailable,
                                    ])
                                >
                                    {{ $isCompleted ? '✅' : '⭐' }}
                                    @if ($isAvailable)
                                        <span
                                            class="absolute -top-1 -end-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-amber-950 shadow"
                                            data-station-highlight
                                            aria-hidden="true"
                                        >
                                            !
                                        </span>
                                        <span class="absolute inset-0 motion-safe:animate-ping rounded-full border-2 border-amber-300 opacity-40" aria-hidden="true"></span>
                                    @endif
                                </span>
                                <div class="min-w-0 text-start" dir="auto">
                                    <p @class([
                                        'truncate font-semibold',
                                        'text-emerald-900 dark:text-emerald-100' => $isCompleted,
                                        'text-amber-950 dark:text-amber-50' => $isAvailable,
                                    ])>{{ $node['title'] }}</p>
                                    <p class="text-sm {{ $isCompleted ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-200' }}">
                                        {{ $isCompleted ? 'مكتمل — راجع أو أعد الاختبار' : 'المحطة التالية — ابدأ الآن!' }}
                                    </p>
                                </div>
                            </a>
                        @endif

                        @if ($isAvailable)
                            <div class="absolute -end-2 -top-8 sm:-end-4 sm:-top-10">
                                <x-student.mascot
                                    state="happy"
                                    message="هيا إلى المحطة التالية!"
                                    size="sm"
                                />
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
