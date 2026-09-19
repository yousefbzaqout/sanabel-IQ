@php
    /** @var array<string, mixed> $lesson */
    $stations = $lesson['stations'] ?? [];
    $lessonDemoKey = (string) ($lesson['lesson_key'] ?? $lesson['key'] ?? 'lesson');

    /** @var list<array<string, mixed>> $tabs */
    $tabs = $stations[1]['config']['tabs'] ?? $lesson['diacritic_tabs'] ?? [];
    $tabs = array_map(static function (array $tab): array {
        $tab['cards'] = array_map(static function (array $card): array {
            $card['audio'] = (string) ($card['audio'] ?? $card['audio_script'] ?? '');

            return $card;
        }, $tab['cards'] ?? []);

        return $tab;
    }, $tabs);

    /** @var list<array<string, mixed>> $bubbles */
    $bubbles = $stations[2]['config']['syllables'] ?? $lesson['syllables'] ?? [
        ['glyph' => 'رَ', 'order' => 1],
        ['glyph' => 'مَ', 'order' => 2],
        ['glyph' => 'لْ', 'order' => 3],
    ];

    /** @var list<array<string, mixed>> $rawPositions */
    $rawPositions = $stations[3]['config']['cards'] ?? $lesson['positions'] ?? [];
    $positions = array_map(static function (array $card): array {
        return [
            'id' => (string) ($card['id'] ?? ''),
            'label' => (string) ($card['label'] ?? ''),
            'word' => (string) ($card['display_word'] ?? $card['word'] ?? ''),
            'parts' => $card['parts'] ?? [],
            'audio' => (string) ($card['audio'] ?? $card['audio_script'] ?? ''),
        ];
    }, $rawPositions);

    $traceConfig = $stations[4]['config'] ?? [];
    $tracePath = (string) ($traceConfig['paths'][0]['d'] ?? $lesson['tracing']['path'] ?? 'M 70 28 C 92 28 108 48 108 72 C 108 96 92 112 70 112 C 48 112 36 96 36 78');
    $traceViewBox = (string) ($traceConfig['view_box'] ?? $lesson['tracing']['view_box'] ?? '0 0 140 140');
    $traceCompleteAudio = (string) ($traceConfig['complete_audio_script'] ?? $lesson['tracing']['complete_audio'] ?? '');

    $scratchConfig = $stations[5]['config'] ?? [];
    $sandStory = (string) ($scratchConfig['story_audio_script'] ?? $lesson['discovery']['story_audio'] ?? 'مرحباً! هذه بيارة الرمان. انظر… رُمّان تبدأ بحرف الراء!');
    /** @var list<array<string, mixed>> $scratchHotspots */
    $scratchHotspots = $scratchConfig['hotspots'] ?? $lesson['discovery']['items'] ?? [];
    $scratchCorrect = array_values(array_filter(
        $scratchHotspots,
        static fn (array $item): bool => (bool) ($item['correct'] ?? false),
    ));
    if ($scratchCorrect === []) {
        $scratchCorrect = $scratchHotspots;
    }
    $scratchHeadline = (string) ($scratchCorrect[0]['label'] ?? ($stations[5]['title'] ?? 'اكتشف'));
    $scratchEmojis = array_values(array_map(
        static fn (array $item): string => (string) ($item['emoji'] ?? '✨'),
        array_slice($scratchCorrect, 0, 3),
    ));
    if ($scratchEmojis === []) {
        $scratchEmojis = ['🌳', '🍎', '🍊'];
    }

    $guidedConfig = $stations[6]['config'] ?? [];
    $demo = [
        'question' => (string) ($guidedConfig['question_text'] ?? $lesson['demo']['question'] ?? ''),
        'parts' => $guidedConfig['parts'] ?? $lesson['demo']['parts'] ?? [],
        'explain_audio' => (string) ($guidedConfig['explain_script'] ?? $lesson['demo']['explain_audio'] ?? ''),
        'cta' => (string) ($guidedConfig['cta_label'] ?? $lesson['demo']['cta'] ?? 'جاهز للاختبار يا بطل! 🎯'),
    ];

    $stationLabels = $lesson['demo_station_labels'] ?? ($lesson['state_labels'] ?? []);
    $wordCompleteAudio = (string) ($stations[2]['config']['completion_audio_script'] ?? $lesson['word_complete_audio'] ?? 'أحسنت! كوّنت كلمة رَمَل');
    $targetWord = (string) ($stations[2]['config']['target_word'] ?? $lesson['target_word'] ?? 'رَمَل');
    $sequenceWordPieces = [];
    foreach ($bubbles as $bubble) {
        $order = (int) ($bubble['order'] ?? 0);
        if ($order > 0) {
            $sequenceWordPieces[$order] = (string) ($bubble['glyph'] ?? '');
        }
    }
    if ($sequenceWordPieces === []) {
        $sequenceWordPieces = [1 => 'رَ', 2 => 'مَ', 3 => 'لْ'];
    }
    $sequenceLength = count($sequenceWordPieces);
    $sequenceVoiceTarget = (string) ($bubbles[0]['glyph'] ?? ($stations[1]['config']['tabs'][0]['glyph'] ?? 'رَ'));
    $sequenceCompleteLabel = str_contains($targetWord, ' ') || mb_strlen($targetWord) > 6
        ? 'أحسنت! أكملت الترتيب ⭐'
        : 'أحسنت! أكملت الكلمة ⭐';

    $stationType = static fn (int $number): string => (string) ($stations[$number]['type'] ?? '');
@endphp

<div
    data-lesson-demo="{{ $lessonDemoKey }}"
    data-current-station="{{ $currentStation }}"
    dir="rtl"
    class="relative flex min-h-dvh flex-col overflow-y-auto bg-background text-on-surface font-body-md text-body-md antialiased"
    x-data="integratedLetterRaaDemo(@js([
        'traceCompleteAudio' => $traceCompleteAudio,
        'demoAudio' => $demo['explain_audio'],
        'sandStory' => $sandStory,
        'wordCompleteAudio' => $wordCompleteAudio,
        'wordPieces' => $sequenceWordPieces,
        'sequenceLength' => $sequenceLength,
        'sequenceVoiceTarget' => $sequenceVoiceTarget,
    ]))"
    x-init="bootStation({{ $currentStation }})"
    x-on:mascot-speak.window="speakMascot($event.detail.message || $event.detail[0]?.message || '')"
>
    <header class="relative z-20 flex items-center justify-between gap-space-md px-gutter-mobile md:px-margin py-space-md">
        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex min-h-12 min-w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface hover:bg-error-container hover:text-on-error-container transition-all shadow-sm"
            aria-label="العودة للوحة التعلم"
        ><span class="material-symbols-outlined">arrow_forward</span></a>
        <div class="rounded-full bg-surface-container-lowest px-space-md py-space-xs text-center shadow-sm">
            <p class="font-label-sm text-label-sm font-semibold text-secondary">{{ $lesson['subtitle'] ?? 'رحلة الشرح والتأسيس' }}</p>
            <p class="font-label-lg text-label-lg font-black text-on-surface">{{ $lesson['title'] ?? 'حرف الراء' }}</p>
        </div>
        <div class="rounded-full bg-primary-container px-space-md py-space-xs font-label-lg text-label-lg font-bold text-on-primary-container shadow-sm">
            {{ $currentStation }} / 6
        </div>
    </header>

    <div
        data-smart-mascot
        class="relative z-20 mx-auto mb-2 flex w-full max-w-3xl items-center justify-center gap-3 px-4 sm:px-6"
        wire:key="smart-mascot-{{ $currentStation }}-{{ $mascotTone }}"
    >
        <x-student.mascot
            :state="$mascotState"
            :message="$mascotMessage"
            size="sm"
        />
    </div>

    @if ($showMicroHint)
        <div
            data-micro-hint
            data-micro-hint-concept="{{ $pendingHintConcept }}"
            class="relative z-20 mx-auto mb-4 w-full max-w-3xl px-4 sm:px-6"
            wire:key="micro-hint-{{ $pendingHintConcept }}"
        >
            <div class="rounded-[1.75rem] border-2 border-amber-300 bg-amber-50 p-5 text-center shadow-lg">
                <p class="text-sm font-bold text-amber-800">بطاقة تلميح سنبل</p>
                <p class="mt-2 text-lg font-black text-amber-950">{{ $microHintMessage }}</p>
                <button
                    type="button"
                    wire:click="dismissMicroHint"
                    class="mt-4 inline-flex min-h-12 items-center justify-center rounded-2xl bg-emerald-700 px-6 font-bold text-white shadow hover:bg-emerald-600"
                >
                    فهمت! تابع
                </button>
            </div>
        </div>
    @endif

    <nav class="relative z-20 mx-auto flex w-full max-w-4xl gap-2 overflow-x-auto px-gutter-mobile md:px-margin pb-space-sm" aria-label="محطات الدرس">
        @foreach ($stationLabels as $number => $label)
            <button
                type="button"
                data-station-tab="{{ (int) $number }}"
                wire:click="goToStation({{ (int) $number }})"
                @class([
                    'inline-flex min-h-12 shrink-0 items-center justify-center rounded-full px-space-md font-label-lg text-label-lg font-bold transition',
                    'bg-primary-container text-on-primary-container shadow-sm' => (int) $currentStation === (int) $number,
                    'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' => (int) $currentStation !== (int) $number,
                ])
            >{{ $label }}</button>
        @endforeach
    </nav>

    <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col px-gutter-mobile md:px-margin pb-space-xl">
        {{-- Station 1: Pronunciation Lab (Stitch) --}}
        <section
            data-lesson-station="1"
            data-station-type="{{ $stationType(1) ?: 'variant_matrix' }}"
            @class(['flex flex-1 flex-col gap-space-lg pt-space-sm' => true, 'hidden' => $currentStation !== 1])
        >
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md bg-surface-container-lowest p-space-md md:p-space-lg rounded-lg shadow-sm relative overflow-hidden">
                <div class="absolute -top-12 -left-12 w-40 h-40 bg-secondary-fixed/30 rounded-full blur-3xl pointer-events-none"></div>
                <div class="flex flex-col gap-space-xs relative z-10">
                    <div class="flex flex-wrap items-center gap-space-xs">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-secondary-container/60 text-on-secondary-container font-label-md text-label-md font-bold">
                            <span class="material-symbols-outlined text-sm">auto_awesome</span>
                            محطة الذكاء الاصطناعي: التقييم الصوتي الذكي 🎙️
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                            تحليل نبرة فوري
                        </span>
                    </div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-extrabold tracking-tight mt-1">{{ $stations[1]['title'] ?? 'الحركات الثلاث' }} 🎵</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $stations[1]['instructions'] ?? 'اضغط الحركة، اسمع البطاقات، ثم انطقها في الميكروفون.' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
                <div class="lg:col-span-7 flex flex-col gap-space-lg">
                    <div class="bg-surface-container-lowest rounded-lg p-space-lg md:p-space-xl shadow-md relative overflow-hidden flex flex-col items-center text-center">
                        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-primary-container via-secondary to-primary-container"></div>
                        <div class="w-full flex justify-between items-center mb-space-sm">
                            <span class="px-space-sm py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-md text-label-md font-bold">صوت الحرف المستهدف</span>
                            <span class="material-symbols-outlined text-secondary text-2xl">graphic_eq</span>
                        </div>

                        <div class="flex flex-wrap justify-center gap-space-sm my-space-md">
                            @foreach ($tabs as $tab)
                                <button
                                    type="button"
                                    data-diacritic="{{ $tab['glyph'] }}"
                                    class="inline-flex min-h-14 min-w-16 items-center justify-center rounded-2xl px-space-md text-3xl font-black shadow transition"
                                    :class="diacriticTab === @js($tab['glyph']) ? 'bg-primary-container text-on-primary-container ring-4 ring-primary-fixed scale-105' : 'bg-surface-container-low text-on-surface'"
                                    @click="setDiacriticTab(@js($tab['glyph']))"
                                    aria-label="حركة {{ $tab['glyph'] }}"
                                >{{ $tab['glyph'] }}</button>
                            @endforeach
                        </div>

                        <div class="relative my-space-sm flex items-center justify-center">
                            <div class="absolute w-44 h-44 rounded-full bg-primary-fixed-dim/30 filter blur-xl animate-pulse"></div>
                            <div class="w-40 h-40 rounded-3xl bg-surface-container-low flex flex-col items-center justify-center shadow-inner relative z-10">
                                <span class="font-headline-lg text-headline-lg text-primary font-black select-none scale-125" x-text="diacriticTab"></span>
                            </div>
                        </div>

                        @foreach ($tabs as $tab)
                            <div
                                class="grid w-full grid-cols-1 gap-space-sm sm:grid-cols-3 mt-space-md"
                                x-show="diacriticTab === @js($tab['glyph'])"
                                @unless ($loop->first) x-cloak @endunless
                            >
                                @foreach ($tab['cards'] as $card)
                                    <button
                                        type="button"
                                        class="flex min-h-28 flex-col items-center justify-center gap-2 rounded-lg bg-surface-container p-space-md text-center shadow-sm transition hover:bg-surface-container-high"
                                        @click="speak(@js($card['audio']))"
                                    >
                                        <span class="text-3xl" aria-hidden="true">{{ $card['emoji'] }}</span>
                                        <span class="text-2xl font-black text-on-surface">
                                            <span class="rounded-lg bg-primary-fixed px-1 text-on-primary-fixed">{{ $card['highlight'] }}</span>{{ mb_substr($card['word'], mb_strlen($card['highlight'])) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-surface-container-lowest rounded-lg p-space-lg md:p-space-xl shadow-md flex flex-col items-center text-center relative overflow-hidden">
                        <div class="inline-flex items-center gap-space-xs px-space-md py-1.5 rounded-full bg-primary-fixed text-on-primary-fixed mb-space-md font-label-md text-label-md font-bold">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary animate-ping"></span>
                            🎙️ سنبل يستمع إليك الآن... انطق بوضوح!
                        </div>

                        <div class="relative my-space-lg flex items-center justify-center">
                            <div class="absolute w-36 h-36 rounded-full bg-primary-container/25 animate-ping duration-1000" x-show="listening"></div>
                            <div class="absolute w-32 h-32 rounded-full bg-secondary-container/50 animate-pulse" x-show="listening"></div>
                            <button
                                type="button"
                                data-voice-mic
                                aria-label="تسجيل نطق الحركة"
                                class="relative z-10 w-28 h-28 rounded-full bg-gradient-to-tr from-primary-container via-primary-fixed-dim to-secondary-fixed text-on-primary-fixed flex flex-col items-center justify-center shadow-xl hover:scale-105 active:scale-95 transition-all"
                                :class="listening && 'scale-110 ring-4 ring-primary-fixed'"
                                @click="listenPronunciation(diacriticTab)"
                            >
                                <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">mic</span>
                                <span class="font-label-sm text-label-sm font-black mt-1" x-text="listening ? '… يستمع' : 'تحدث الآن'"></span>
                            </button>
                        </div>

                        <p
                            class="min-h-8 rounded-full px-space-md py-space-xs font-label-lg text-label-lg font-bold"
                            data-voice-feedback
                            :class="voiceResult === 'match' ? 'bg-secondary-fixed text-on-secondary-fixed' : (voiceResult === 'retry' ? 'bg-primary-fixed text-on-primary-fixed' : 'text-on-surface-variant')"
                            x-text="voiceFeedback || '🎙️ انطق الحركة'"
                        ></p>
                    </div>
                </div>

                <div class="lg:col-span-5 flex flex-col gap-space-lg">
                    <div class="bg-gradient-to-br from-primary-fixed/40 via-surface-container-lowest to-secondary-fixed/20 rounded-lg p-space-lg shadow-md flex flex-col relative overflow-hidden">
                        <div class="flex items-center gap-space-md">
                            <div class="relative w-24 h-24 rounded-3xl bg-surface-container-lowest p-1.5 shadow-md shrink-0 flex items-center justify-center overflow-hidden">
                                <img src="{{ asset('brand/mascot-sanbal-storybook.jpg') }}" alt="سنبل" class="w-full h-full object-contain rounded-2xl">
                            </div>
                            <div class="flex flex-col text-right">
                                <div class="flex items-center gap-1">
                                    <span class="font-label-lg text-label-lg font-extrabold text-primary">المعلم الذكي سُنبُل</span>
                                    <span class="material-symbols-outlined text-secondary text-sm" style="font-variation-settings: 'FILL' 1;">verified</span>
                                </div>
                                <span class="font-body-sm text-body-sm text-on-surface-variant font-medium">رفيقك في إتقان فصاحة العربية</span>
                            </div>
                        </div>
                        <div class="relative mt-space-md bg-surface-container-lowest p-space-md rounded-2xl shadow-sm text-right">
                            <div class="absolute -top-2 right-8 w-4 h-4 bg-surface-container-lowest rotate-45"></div>
                            <p class="font-body-md text-body-md font-bold text-on-surface leading-relaxed">{{ $mascotMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Station 2: Bubble pop game --}}
        <section
            data-lesson-station="2"
            data-station-type="{{ $stationType(2) ?: 'sequence_pop' }}"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentStation !== 2])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">{{ $stations[2]['title'] ?? 'فرقعة الفقاعات' }} 🎈</h2>
            <p class="text-center text-emerald-800">{{ $stations[2]['instructions'] ?? 'فرقع الفقاعات بالترتيب، وجرّب نطق رَ في الميكروفون.' }}</p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <button
                    type="button"
                    data-voice-mic
                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-rose-500 px-5 text-base font-bold text-white shadow transition hover:bg-rose-400"
                    :class="listening && 'motion-safe:animate-pulse ring-4 ring-rose-200'"
                    @click="listenPronunciation(sequenceVoiceTarget)"
                    :aria-label="'تسجيل نطق ' + sequenceVoiceTarget"
                >
                    <span x-text="listening ? '… يستمع' : ('🎙️ انطق ' + sequenceVoiceTarget)"></span>
                </button>
                <p
                    class="min-h-8 rounded-xl px-3 py-1 text-sm font-bold"
                    data-voice-feedback
                    :class="voiceResult === 'match' ? 'bg-emerald-200 text-emerald-900' : (voiceResult === 'retry' ? 'bg-amber-200 text-amber-950' : 'text-emerald-800')"
                    x-text="voiceFeedback"
                ></p>
            </div>

            <div
                data-bubble-mechanic
                class="relative min-h-72 overflow-hidden rounded-[2rem] border-2 border-sky-200 bg-gradient-to-b from-sky-100 via-cyan-50 to-emerald-50 p-6 shadow-inner"
            >
                <p class="mb-4 text-center text-sm font-bold text-sky-900">الترتيب: {{ collect($bubbles)->pluck('glyph')->implode(' ← ') }}</p>
                <div class="relative flex min-h-44 flex-wrap items-center justify-center gap-5">
                    @foreach ($bubbles as $index => $bubble)
                        <button
                            type="button"
                            data-sound-bubble="{{ $bubble['glyph'] }}"
                            class="inline-flex min-h-20 min-w-20 items-center justify-center rounded-full bg-sky-300/95 text-3xl font-black text-sky-950 shadow-lg transition hover:scale-110 motion-safe:animate-bounce"
                            style="animation-delay: {{ $index * 140 }}ms"
                            :class="popped[@js($bubble['glyph'])] && 'scale-0 opacity-0 pointer-events-none'"
                            @click="popBubble(@js($bubble['glyph']), {{ (int) $bubble['order'] }})"
                            aria-label="فقاعة {{ $bubble['glyph'] }}"
                        >{{ $bubble['glyph'] }}</button>
                    @endforeach
                </div>
                <p
                    class="mt-5 text-center text-4xl font-black tracking-wide text-amber-900 transition"
                    :class="wordComplete && 'motion-safe:animate-bounce text-emerald-700'"
                    x-text="builtWord || '…'"
                    data-built-word
                ></p>
                <p class="mt-2 text-center text-base font-bold text-emerald-800" x-show="wordComplete" x-cloak>{{ $sequenceCompleteLabel }}</p>
                <p class="sr-only" data-target-word>{{ $targetWord }}</p>
            </div>
        </section>

        {{-- Station 3: Positions --}}
        <section
            data-lesson-station="3"
            data-station-type="{{ $stationType(3) ?: 'structure_cards' }}"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentStation !== 3])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">{{ $stations[3]['title'] ?? 'مواقع الحرف' }} 📍</h2>
            <p class="text-center text-emerald-800">{{ $stations[3]['instructions'] ?? 'اضغط البطاقة لترى أين يختبئ حرف الراء.' }}</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($positions as $card)
                    <button
                        type="button"
                        data-position-card="{{ $card['id'] }}"
                        class="flex min-h-36 flex-col items-center justify-center gap-3 rounded-[1.75rem] border-2 border-emerald-200 bg-white/90 p-5 shadow-md transition hover:border-amber-400"
                        @click="activatePosition(@js($card['id']), @js($card['audio']))"
                    >
                        <span class="text-sm font-bold text-emerald-700">{{ $card['label'] }}</span>
                        <span class="flex flex-wrap items-center justify-center gap-0.5 text-4xl font-black" dir="rtl">
                            @foreach ($card['parts'] as $part)
                                <span
                                    class="inline-flex min-h-12 min-w-12 items-center justify-center rounded-xl px-1"
                                    :class="activePosition === @js($card['id']) && {{ $part['highlight'] ? 'true' : 'false' }}
                                        ? 'bg-emerald-300 motion-safe:animate-pulse shadow-[0_0_18px_rgba(52,211,153,.8)]'
                                        : ''"
                                >{{ $part['text'] }}</span>
                            @endforeach
                        </span>
                        <span class="sr-only">{{ $card['word'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Station 4: Stroke Tracing Studio (Stitch) --}}
        <section
            data-lesson-station="4"
            data-station-type="{{ $stationType(4) ?: 'trace_canvas' }}"
            @class(['flex flex-1 flex-col gap-space-lg pt-space-sm' => true, 'hidden' => $currentStation !== 4])
        >
            <div class="w-full bg-surface-container-lowest rounded-lg p-space-md md:p-space-lg shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-space-md">
                <div class="flex flex-wrap items-center gap-space-sm">
                    <div class="flex items-center gap-space-xs bg-primary-fixed text-on-primary-fixed px-space-md py-space-xs rounded-full shadow-sm">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">draw</span>
                        <span class="font-label-lg text-label-lg font-bold">{{ $stations[4]['title'] ?? 'التتبع بالإصبع' }} ✍️</span>
                    </div>
                    <div class="flex items-center gap-space-xs bg-secondary-fixed text-on-secondary-fixed px-space-md py-space-xs rounded-full">
                        <span class="material-symbols-outlined text-secondary text-sm">psychology</span>
                        <span class="font-label-sm text-label-sm">{{ $stations[4]['instructions'] ?? 'ارسم حرف الراء، وسينزل سنبل على الخط!' }}</span>
                    </div>
                </div>
            </div>

            <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
                <div class="lg:col-span-8 flex flex-col gap-space-md">
                    <div class="relative w-full bg-surface-container-lowest rounded-xl p-space-md md:p-space-lg shadow-md overflow-hidden flex flex-col">
                        <div
                            data-trace-canvas
                            class="relative w-full h-[320px] md:h-[420px] bg-[#FAF9F5] rounded-lg overflow-hidden select-none cursor-crosshair touch-none shadow-inner flex items-center justify-center"
                            @pointerdown.prevent="startTrace($event)"
                            @pointermove.prevent="moveTrace($event)"
                            @pointerup.prevent="endTrace()"
                            @pointerleave.prevent="endTrace()"
                        >
                            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <line stroke="#94A3B8" stroke-dasharray="6,6" stroke-width="1.5" x1="0" x2="100%" y1="28%" y2="28%"></line>
                                <line stroke="#F59E0B" stroke-width="2.5" x1="0" x2="100%" y1="62%" y2="62%"></line>
                                <line stroke="#006A61" stroke-dasharray="4,4" stroke-width="1.5" x1="0" x2="100%" y1="82%" y2="82%"></line>
                            </svg>
                            <svg viewBox="{{ $traceViewBox }}" class="relative z-10 h-64 w-full max-w-md select-none" aria-label="لوحة تتبع حرف الراء">
                                <path d="{{ $tracePath }}" fill="none" stroke="#E2E8F0" stroke-width="16" stroke-linecap="round" />
                                <path d="{{ $tracePath }}" fill="none" stroke="#94A3B8" stroke-width="6" stroke-linecap="round" stroke-dasharray="8 12" class="motion-safe:animate-pulse" />
                                <path
                                    d="{{ $tracePath }}"
                                    fill="none"
                                    stroke="#F59E0B"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="100"
                                    pathLength="100"
                                    :stroke-dashoffset="traceOffset"
                                    style="filter: drop-shadow(0 4px 12px rgba(245, 158, 11, 0.45));"
                                />
                            </svg>

                            <div
                                data-sonbol-slider
                                class="pointer-events-none absolute start-1/2 top-6 -translate-x-1/2 transition-all duration-700 z-20"
                                :style="`transform: translate(-50%, ${traceDone ? '9rem' : '0'}); opacity: ${traceDone ? 1 : 0.35}`"
                            >
                                <div class="origin-top scale-50">
                                    <x-student.mascot :state="$mascotState" message="هيا نرسم!" :show-bubble="false" size="sm" />
                                </div>
                            </div>
                        </div>

                        <p class="mt-space-md text-center font-label-lg text-label-lg font-bold text-on-surface" x-text="traceFeedback || (traceDone ? 'سنبل نزل على الخط! ⭐' : 'مرّر إصبعك من الأعلى للأسفل')"></p>
                        <p class="mt-1 text-center font-label-sm text-label-sm font-semibold text-secondary" x-show="strokeResult" x-text="strokeResult === 'match' ? 'اتجاه الرسم صحيح ✓' : 'حاول من الأعلى للأسفل'" x-cloak></p>
                    </div>
                </div>

                <div class="lg:col-span-4 flex flex-col gap-space-md">
                    <div class="w-full bg-surface-container-lowest rounded-xl p-space-lg shadow-md flex flex-col gap-space-md">
                        <div class="flex items-center justify-between pb-space-xs">
                            <div class="flex items-center gap-space-xs">
                                <span class="material-symbols-outlined text-primary">verified</span>
                                <span class="font-headline-sm text-headline-sm font-bold text-on-surface">التحليل الذكي للمسار</span>
                            </div>
                            <span class="px-space-sm py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-bold">فوري ⚡</span>
                        </div>

                        <div class="mt-space-xs bg-primary-fixed/40 rounded-lg p-space-md flex items-start gap-space-md">
                            <div class="relative w-16 h-16 flex-shrink-0">
                                <img alt="سنبل" class="w-16 h-16 object-contain rounded-full bg-surface-container-lowest shadow-sm p-1" src="{{ asset('brand/mascot-sanbal-storybook.jpg') }}">
                                <span class="absolute -bottom-1 -right-1 text-base">🐥</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="font-label-md text-label-md font-bold text-on-primary-fixed">المعلم سنبل الذكي:</span>
                                <p class="font-body-sm text-body-sm text-on-surface leading-relaxed">{{ $mascotMessage }}</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="nextStation"
                            class="w-full h-14 rounded-full bg-primary-container text-on-primary-container font-headline-sm text-headline-sm font-bold shadow-[0_4px_0_#D97706] hover:translate-y-0.5 active:translate-y-1 transition-transform flex items-center justify-center gap-space-xs"
                            x-show="traceDone"
                            x-cloak
                        >
                            <span>حفظ الرسمة والتقدم</span>
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Station 5: Sand scratch --}}
        <section
            data-lesson-station="5"
            data-station-type="{{ $stationType(5) ?: 'scratch_discover' }}"
            @class(['flex flex-1 flex-col gap-5 pt-2' => true, 'hidden' => $currentStation !== 5])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">{{ $stations[5]['title'] ?? 'الاستكشاف البيئي' }} ⏳</h2>
            <p class="text-center text-emerald-800">{{ $stations[5]['instructions'] ?? 'امسح الرمل عن بيارة الرمان.' }}</p>

            <div
                data-sand-scratch
                class="relative mx-auto h-72 w-full max-w-lg touch-none overflow-hidden rounded-[2rem] border-2 border-amber-300 shadow-xl"
                @pointerdown.prevent="startScratch($event)"
                @pointermove.prevent="scratch($event)"
                @pointerup.prevent="endScratch()"
                @pointerleave.prevent="endScratch()"
            >
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-b from-lime-200 via-amber-100 to-orange-200 p-6 text-center">
                    <p class="text-lg font-bold text-emerald-900">{{ $scratchHeadline }}</p>
                    <div class="flex gap-4 text-5xl" aria-hidden="true">
                        @foreach ($scratchEmojis as $emoji)
                            <span>{{ $emoji }}</span>
                        @endforeach
                    </div>
                    <p class="text-2xl font-black text-rose-800">{{ $scratchHeadline }}</p>
                    <p class="text-sm text-emerald-800" x-show="sandRevealed" x-cloak>{{ $sandStory }}</p>
                </div>
                <canvas
                    x-ref="sandCanvas"
                    class="absolute inset-0 h-full w-full cursor-crosshair"
                    :class="sandCleared && 'pointer-events-none opacity-0 transition-opacity duration-700'"
                ></canvas>
                <p class="pointer-events-none absolute inset-x-0 bottom-3 text-center text-xs font-bold text-amber-950/80" x-show="!sandCleared">
                    امسح بإصبعك ⏳
                </p>
            </div>
        </section>

        {{-- Station 6: Little teacher + CTA --}}
        <section
            data-lesson-station="6"
            data-station-type="{{ $stationType(6) ?: 'guided_demo' }}"
            @class(['flex flex-1 flex-col items-center gap-6 pt-2' => true, 'hidden' => $currentStation !== 6])
        >
            <h2 class="text-center text-2xl font-black text-emerald-950">{{ $stations[6]['title'] ?? 'المعلم الصغير' }} 🎙️</h2>
            <x-student.mascot :state="$mascotState" :message="$demo['question'] ?? ''" size="lg" />

            <div class="w-full rounded-[2rem] border border-emerald-200 bg-white/90 p-6 text-center shadow-lg sm:p-8">
                <p class="text-lg font-semibold text-emerald-800">{{ $demo['question'] ?? '' }}</p>
                <p class="mt-6 flex flex-wrap items-center justify-center gap-1 text-5xl font-black text-emerald-950" dir="rtl">
                    @foreach (($demo['parts'] ?? []) as $part)
                        <span
                            class="inline-flex min-h-14 min-w-14 items-center justify-center rounded-xl px-2 transition"
                            @if ($part['highlight'])
                                :class="demoGlow ? 'bg-emerald-400 shadow-[0_0_24px_rgba(52,211,153,.85)] scale-110' : 'bg-emerald-100'"
                            @endif
                        >{{ $part['text'] }}</span>
                    @endforeach
                </p>
                <button
                    type="button"
                    class="mt-6 inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-amber-400 px-5 font-bold text-amber-950 shadow hover:bg-amber-300"
                    @click="playDemo()"
                >🔊 اسمع المحاكاة</button>
            </div>

            <a
                href="{{ $quizUrl }}"
                class="inline-flex min-h-14 min-w-[min(100%,20rem)] items-center justify-center rounded-3xl bg-gradient-to-l from-amber-400 to-orange-500 px-8 text-xl font-black text-amber-950 shadow-xl hover:brightness-105"
            >{{ $demo['cta'] }}</a>
        </section>

        <div class="mt-auto flex flex-wrap justify-center gap-space-md pt-space-lg">
            @if ($currentStation > 1)
                <button type="button" wire:click="previousStation" class="inline-flex min-h-12 items-center justify-center rounded-full bg-surface-container-lowest px-space-lg font-label-lg text-label-lg font-bold text-on-surface shadow-sm hover:bg-surface-container">السابق</button>
            @endif
            @if ($currentStation < 6)
                <button type="button" wire:click="nextStation" class="inline-flex min-h-12 items-center justify-center rounded-full bg-primary-container px-space-xl text-lg font-bold text-on-primary-container shadow-[0_4px_0_#D97706] hover:brightness-105">التالي ←</button>
            @endif
        </div>
    </div>
</div>
