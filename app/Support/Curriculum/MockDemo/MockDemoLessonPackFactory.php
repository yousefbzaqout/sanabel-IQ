<?php

declare(strict_types=1);

namespace App\Support\Curriculum\MockDemo;

/**
 * Builds schema-valid Palestinian lesson packs for demo/marketing content.
 */
final class MockDemoLessonPackFactory
{
    private const EASTERN = ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'];

    /**
     * @param  array{
     *     slug: string,
     *     title: string,
     *     unit_title: string,
     *     glyph: string,
     *     focus_words: list<string>,
     *     emojis: list<string>,
     *     summary: string
     * }  $topic
     * @return array<string, mixed>
     */
    public function make(
        int $grade,
        int $semester,
        string $subject,
        array $topic,
        int $lessonIndex,
        int $orderColumn,
    ): array {
        $subject = strtolower($subject);
        $meta = $this->subjectMeta($subject);
        $isMath = $meta['content_kind'] === 'number';
        $slug = (string) $topic['slug'];
        $key = sprintf('mock-g%d-%s-%s', $grade, $subject, $slug);
        $lessonKey = sprintf('ar-g%d-%s-mock-%s', $grade, $subject === 'math' ? 'math' : ($subject === 'science' ? 'sci' : 'ar'), $slug);
        $glyph = (string) $topic['glyph'];
        $words = array_values($topic['focus_words']);
        $emojis = array_values($topic['emojis']);
        $title = (string) $topic['title'];

        $pack = [
            'schema_version' => '1.0.0',
            'grade_level' => $grade,
            'semester' => $semester,
            'subject_code' => $meta['code'],
            'content_kind' => $meta['content_kind'],
            'unit_title' => (string) $topic['unit_title'],
            'lesson_key' => $lessonKey,
            'audio_slug' => $slug,
            'key' => $key,
            'title' => $title,
            'subtitle' => sprintf('عرض تجريبي — الصف %s — الفصل %d', $this->arabicGrade($grade), $semester),
            'material_title' => sprintf('تجريبي G%d: %s', $grade, $title),
            'order_column' => $orderColumn,
            'xp_reward' => 40 + ($grade * 2),
            'description' => (string) $topic['summary'],
            'phonemes' => $this->phonemes($isMath, $glyph, $words, $emojis),
            'sequence' => $this->sequence($isMath, $glyph, $words),
            'stroke' => $this->stroke($isMath, $glyph, $title),
            'discovery' => $this->discovery($title, $words, $emojis),
            'demo' => $this->demo($title, $words, $emojis, $glyph),
            'mascot_hints' => $this->mascotHints($title, $isMath, $glyph),
            'quiz' => ['questions' => $this->quizQuestions($isMath, $glyph, $words, $emojis, $title)],
        ];

        if ($isMath) {
            $digits = $this->digitsFromGlyph($glyph);
            $pack['digits'] = $digits;
            $pack['digit'] = $digits[array_key_last($digits)];
            $pack['number_forms'] = $this->numberForms($pack['digit'], $emojis);
        } else {
            $pack['letter'] = mb_substr($glyph, 0, 1);
            $pack['positions'] = $this->letterPositions($pack['letter'], $words);
        }

        return $pack;
    }

    /**
     * @return array{code: string, content_kind: string, name: string, icon: string}
     */
    public function subjectMeta(string $subject): array
    {
        return match (strtolower($subject)) {
            'arabic' => ['code' => 'AR', 'content_kind' => 'letter', 'name' => 'اللغة العربية', 'icon' => '📖'],
            'math' => ['code' => 'MATH', 'content_kind' => 'number', 'name' => 'الرياضيات', 'icon' => '🔢'],
            'science' => ['code' => 'SCI', 'content_kind' => 'letter', 'name' => 'العلوم', 'icon' => '🔬'],
            default => throw new \InvalidArgumentException("Unsupported subject: {$subject}"),
        };
    }

    /**
     * @param  list<string>  $words
     * @param  list<string>  $emojis
     * @return array<string, mixed>
     */
    private function phonemes(bool $isMath, string $glyph, array $words, array $emojis): array
    {
        if ($isMath) {
            $digits = $this->digitsFromGlyph($glyph);
            $tabs = [];
            foreach ($digits as $i => $digit) {
                $word = $words[$i % max(count($words), 1)] ?? $digit;
                $emoji = $emojis[$i % max(count($emojis), 1)] ?? '🔢';
                $tabs[] = [
                    'glyph' => $digit,
                    'label' => $word,
                    'audio_script' => "هذا العدد {$digit}",
                    'cards' => [
                        ['word' => "{$digit} {$word}", 'emoji' => $emoji, 'audio' => $word, 'highlight' => $digit],
                        ['word' => $word, 'emoji' => $emoji, 'audio' => "نقول {$word}", 'highlight' => $word],
                        ['word' => $digit, 'emoji' => '🔢', 'audio' => $digit, 'highlight' => $digit],
                    ],
                ];
            }

            return [
                'voice_targets' => $digits,
                'tabs' => $tabs,
            ];
        }

        $letter = mb_substr($glyph, 0, 1);
        $targets = [$letter.'َ', $letter.'ُ', $letter.'ِ'];
        $labels = ['فتحة', 'ضمة', 'كسرة'];
        $tabs = [];
        foreach ($targets as $i => $target) {
            $w1 = $words[$i % count($words)];
            $w2 = $words[($i + 1) % count($words)];
            $w3 = $words[($i + 2) % count($words)];
            $e1 = $emojis[$i % count($emojis)];
            $tabs[] = [
                'glyph' => $target,
                'label' => $labels[$i],
                'audio_script' => "صوت {$target}",
                'cards' => [
                    ['word' => $w1, 'emoji' => $e1, 'audio' => "{$target} مثل {$w1}", 'highlight' => $target],
                    ['word' => $w2, 'emoji' => $emojis[($i + 1) % count($emojis)], 'audio' => $w2, 'highlight' => mb_substr($w2, 0, 1)],
                    ['word' => $w3, 'emoji' => $emojis[($i + 2) % count($emojis)], 'audio' => $w3, 'highlight' => mb_substr($w3, 0, 1)],
                ],
            ];
        }

        return [
            'voice_targets' => $targets,
            'tabs' => $tabs,
        ];
    }

    /**
     * @param  list<string>  $words
     * @return array<string, mixed>
     */
    private function sequence(bool $isMath, string $glyph, array $words): array
    {
        if ($isMath) {
            $digits = $this->digitsFromGlyph($glyph);
            $syllables = [];
            foreach ($digits as $i => $digit) {
                $syllables[] = ['glyph' => $digit, 'order' => $i + 1];
            }

            return [
                'target_word' => implode('', $digits),
                'syllables' => $syllables,
                'completion_audio_script' => 'أحسنت! رتّبت الأعداد بالترتيب الصحيح.',
            ];
        }

        $word = $words[0];

        return [
            'target_word' => $word,
            'syllables' => [
                ['glyph' => mb_substr($word, 0, 1), 'order' => 1],
                ['glyph' => mb_substr($word, 1, 1) ?: 'ـا', 'order' => 2],
                ['glyph' => mb_substr($word, -1), 'order' => 3],
            ],
            'completion_audio_script' => "أحسنت! كوّنت كلمة {$word}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stroke(bool $isMath, string $glyph, string $title): array
    {
        $path = $isMath
            ? 'M 48 34 C 78 28 108 38 108 58 C 108 74 88 78 70 78 C 92 78 112 88 112 108 C 112 126 86 132 52 126'
            : 'M 36 28 C 36 70 36 110 36 118 M 36 70 C 70 55 100 55 112 70 C 100 88 70 95 36 90';

        return [
            'label' => $isMath ? "تتبّع كتابة {$glyph}" : "تتبّع رمز درس {$title}",
            'path' => $path,
            'direction' => $isMath ? 'digit_stroke' : 'rtl_top_down',
            'view_box' => '0 0 140 140',
            'complete_audio' => 'أحسنت يا بطل! تتبّع رائع مع سنبل.',
        ];
    }

    /**
     * @param  list<string>  $words
     * @param  list<string>  $emojis
     * @return array<string, mixed>
     */
    private function discovery(string $title, array $words, array $emojis): array
    {
        $items = [];
        foreach ($words as $i => $word) {
            $items[] = [
                'id' => 'item-'.$i,
                'label' => $word,
                'emoji' => $emojis[$i % count($emojis)],
                'correct' => true,
                'audio' => $word,
            ];
        }
        $items[] = [
            'id' => 'distractor-cloud',
            'label' => 'سحابة',
            'emoji' => '☁️',
            'correct' => false,
            'audio' => 'هذه ليست من عناصر الدرس',
        ];
        $items[] = [
            'id' => 'distractor-rock',
            'label' => 'صخرة',
            'emoji' => '🪨',
            'correct' => false,
            'audio' => 'جرّب عنصراً آخر',
        ];

        return [
            'intro' => "امسح واعثر على عناصر درس {$title}",
            'story_audio' => "هيا نستكشف {$title} مع سنبل!",
            'items' => $items,
        ];
    }

    /**
     * @param  list<string>  $words
     * @param  list<string>  $emojis
     * @return array<string, mixed>
     */
    private function demo(string $title, array $words, array $emojis, string $glyph): array
    {
        return [
            'question' => "ماذا نتعلم في {$title}؟",
            'parts' => [
                ['text' => $emojis[0], 'highlight' => true],
                ['text' => ' '.$words[0], 'highlight' => true],
                ['text' => " = {$glyph}", 'highlight' => false],
            ],
            'explain_audio' => "في هذا الدرس نتعرّف على {$words[0]} ضمن {$title}.",
            'cta' => 'جاهز للاختبار القصير! 🎯',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mascotHints(string $title, bool $isMath, string $glyph): array
    {
        return [
            'station_prompts' => [
                '1' => "مرحباً! أنا سنبل. هيا نبدأ درس {$title}.",
                '2' => $isMath ? 'رتّب معي الأعداد خطوة بخطوة!' : 'رتّب المقاطع أو الكلمات معي!',
                '3' => $isMath ? "طابق العدد {$glyph} مع أشكاله المختلفة." : 'لاحظ المواقع المختلفة في الكلمات.',
                '4' => 'تتبّع بهدوء ودقة، أنا معك.',
                '5' => 'ابحث عن العناصر الصحيحة في المشهد!',
                '6' => 'شاهد المثال ثم جرّب بنفسك يا بطل.',
            ],
            'adaptive' => [
                ['concept' => 'mock_attention', 'hint' => 'تلميح سنبل: اقرأ السؤال مرتين بهدوء ثم اختر.'],
                ['concept' => 'mock_practice', 'hint' => "تلميح سنبل: ركّز على {$glyph} في كل تمرين."],
            ],
        ];
    }

    /**
     * @param  list<string>  $words
     * @param  list<string>  $emojis
     * @return list<array<string, mixed>>
     */
    private function quizQuestions(bool $isMath, string $glyph, array $words, array $emojis, string $title): array
    {
        $w0 = $words[0];
        $w1 = $words[1] ?? $words[0];
        $w2 = $words[2] ?? $words[0];
        $questions = [];

        if ($isMath) {
            $count = max(1, min(5, (int) preg_replace('/\D+/', '', $this->toWestern($glyph)) ?: 3));
            $questions[] = [
                'type' => 'mcq',
                'prompt' => 'كم عنصراً في المجموعة؟',
                'visual_items' => ['emoji' => $emojis[0], 'count' => $count],
                'mascot_hint' => 'تلميح سنبل: عدّ واحداً واحداً بإصبعك.',
                'explanation' => "المجموعة فيها {$count} عناصر.",
                'options' => [
                    ['text' => $this->toEastern((string) $count), 'correct' => true],
                    ['text' => $this->toEastern((string) max(1, $count - 1)), 'correct' => false],
                    ['text' => $this->toEastern((string) ($count + 1)), 'correct' => false],
                ],
            ];
            $left = max(2, $count);
            $right = max(1, $count - 1);
            $questions[] = [
                'type' => 'mcq',
                'prompt' => 'أي جملة صحيحة؟',
                'comparison' => ['left' => $left, 'right' => $right, 'operator' => 'gt'],
                'mascot_hint' => 'تلميح سنبل: العدد الأكبر يعني كمية أكثر.',
                'explanation' => "{$left} أكبر من {$right}.",
                'options' => [
                    ['text' => "{$left} أكبر من {$right}", 'correct' => true],
                    ['text' => "{$left} أصغر من {$right}", 'correct' => false],
                    ['text' => "{$left} يساوي {$right}", 'correct' => false],
                ],
            ];
        } else {
            $questions[] = [
                'type' => 'mcq',
                'prompt' => "أي كلمة ترتبط بدرس {$title}؟",
                'mascot_hint' => 'تلميح سنبل: اختر الكلمة التي سمعناها في الدرس.',
                'explanation' => "{$w0} من كلمات الدرس الأساسية.",
                'options' => [
                    ['text' => $w0, 'correct' => true],
                    ['text' => 'سحابة بعيدة', 'correct' => false],
                    ['text' => 'رقم عشوائي', 'correct' => false],
                ],
            ];
            $questions[] = [
                'type' => 'mcq',
                'prompt' => 'أي خيار يكمل معنى الدرس؟',
                'mascot_hint' => 'تلميح سنبل: فكّر في الفكرة الرئيسة.',
                'explanation' => "{$w1} جزء مهم من الموضوع.",
                'options' => [
                    ['text' => $w1, 'correct' => true],
                    ['text' => 'لا علاقة', 'correct' => false],
                    ['text' => 'كلمة دخيلة', 'correct' => false],
                ],
            ];
        }

        $questions[] = [
            'type' => 'true_false',
            'prompt' => "كلمة «{$w0}» مناسبة لدرس {$title}.",
            'mascot_hint' => 'تلميح سنبل: هل سمعنا هذه الكلمة اليوم؟',
            'explanation' => 'نعم، هذه من مفردات الدرس.',
            'options' => [
                ['text' => 'صح ✅', 'correct' => true],
                ['text' => 'خطأ ❌', 'correct' => false],
            ],
        ];

        $questions[] = [
            'type' => 'mcq',
            'prompt' => 'ماذا يفعل سنبل عندما نحتار؟',
            'mascot_hint' => 'تلميح سنبل: أنا هنا لأشجّعك!',
            'explanation' => 'سنبل يقدّم تلميحاً لطيفاً ويشجّعنا.',
            'options' => [
                ['text' => 'يقدّم تلميحاً ويشجّعنا', 'correct' => true],
                ['text' => 'يغلق الدرس فوراً', 'correct' => false],
                ['text' => 'يغيّر المادة', 'correct' => false],
            ],
        ];

        if (count($words) >= 3) {
            $questions[] = [
                'type' => 'mcq',
                'prompt' => 'أي مجموعة تنتمي للدرس؟',
                'mascot_hint' => 'تلميح سنبل: اختر المجموعة التي رأيناها معاً.',
                'explanation' => implode('، ', [$w0, $w1, $w2]),
                'options' => [
                    ['text' => "{$w0} / {$w1} / {$w2}", 'correct' => true],
                    ['text' => 'قمر / نجم / مذنب بعيد', 'correct' => false],
                    ['text' => 'صفر / سالب / عشوائي', 'correct' => false],
                ],
            ];
        }

        return $questions;
    }

    /**
     * @param  list<string>  $emojis
     * @return list<array<string, mixed>>
     */
    private function numberForms(string $digit, array $emojis): array
    {
        $n = max(1, min(5, (int) $this->toWestern($digit) ?: 3));
        $emoji = $emojis[0] ?? '⭐';

        return [
            [
                'id' => 'fingers',
                'label' => "{$digit} أصابع",
                'word' => str_repeat('☝️', $n),
                'parts' => array_fill(0, $n, ['text' => '☝️', 'highlight' => true]),
                'audio' => "نعدّ حتى {$digit}",
            ],
            [
                'id' => 'dots',
                'label' => "{$digit} نقاط",
                'word' => str_repeat('●', $n),
                'parts' => array_fill(0, $n, ['text' => '●', 'highlight' => true]),
                'audio' => "{$digit} نقاط",
            ],
            [
                'id' => 'objects',
                'label' => "{$digit} أشياء",
                'word' => str_repeat($emoji, $n),
                'parts' => array_fill(0, $n, ['text' => $emoji, 'highlight' => true]),
                'audio' => "{$digit} {$emoji}",
            ],
        ];
    }

    /**
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function letterPositions(string $letter, array $words): array
    {
        $begin = $words[0];
        $middle = $words[1] ?? $words[0];
        $end = $words[2] ?? $words[0];

        return [
            [
                'label' => 'في البداية',
                'word' => $begin,
                'parts' => [
                    ['text' => mb_substr($begin, 0, 1), 'highlight' => true],
                    ['text' => mb_substr($begin, 1) ?: '', 'highlight' => false],
                ],
                'audio' => "{$begin}: في بداية الكلمة",
            ],
            [
                'label' => 'في الوسط',
                'word' => $middle,
                'parts' => [
                    ['text' => mb_substr($middle, 0, 1), 'highlight' => false],
                    ['text' => $letter, 'highlight' => true],
                    ['text' => mb_substr($middle, -1), 'highlight' => false],
                ],
                'audio' => "{$middle}: موقع متوسط",
            ],
            [
                'label' => 'في النهاية',
                'word' => $end,
                'parts' => [
                    ['text' => mb_substr($end, 0, max(mb_strlen($end) - 1, 1)), 'highlight' => false],
                    ['text' => mb_substr($end, -1), 'highlight' => true],
                ],
                'audio' => "{$end}: في نهاية الكلمة",
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function digitsFromGlyph(string $glyph): array
    {
        $western = $this->toWestern($glyph);
        if ($western !== '' && ctype_digit($western)) {
            $n = (int) $western;
            if ($n >= 10) {
                $chars = preg_split('//u', $this->toEastern($western), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                return array_values($chars);
            }
            $start = max(1, $n - 2);

            return array_map(fn (int $i): string => $this->toEastern((string) $i), range($start, $n));
        }

        // Non-digit math glyphs (shapes/fractions): still provide numeric voice targets.
        return ['١', '٢', '٣'];
    }

    private function toEastern(string $value): string
    {
        return strtr($value, array_flip(self::EASTERN));
    }

    private function toWestern(string $value): string
    {
        return strtr($value, self::EASTERN);
    }

    private function arabicGrade(int $grade): string
    {
        return match ($grade) {
            1 => 'الأول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس',
            6 => 'السادس',
            default => (string) $grade,
        };
    }
}
