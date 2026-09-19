<?php

declare(strict_types=1);

namespace App\Support\Curriculum;

/**
 * Transforms extracted Palestinian curriculum packs (Arabic letters / Math numbers)
 * into {@see \App\Support\Lessons\CurriculumInteractiveLessonImporter} definitions.
 */
final class PalestinianCurriculumTransformer
{
    /**
     * @param  array<string, mixed>  $pack
     * @return array<string, mixed>
     */
    public function toInteractiveDefinition(array $pack): array
    {
        PalestinianLessonSchema::assertValid($pack);

        $contentKind = PalestinianLessonSchema::contentKind($pack);
        $glyph = PalestinianLessonSchema::primaryGlyph($pack);
        $hints = $pack['mascot_hints']['station_prompts'] ?? [];
        $isMath = $contentKind === 'number';

        /** @var list<array<string, mixed>> $tabs */
        $tabs = $pack['phonemes']['tabs'];
        /** @var list<array<string, mixed>> $positions */
        $positions = $pack['positions'] ?? $pack['number_forms'] ?? [];
        /** @var array<string, mixed> $stroke */
        $stroke = $pack['stroke'];
        /** @var array<string, mixed> $discovery */
        $discovery = $pack['discovery'] ?? ['intro' => '', 'story_audio' => '', 'items' => []];
        /** @var array<string, mixed> $demo */
        $demo = $pack['demo'] ?? ['question' => '', 'parts' => [], 'explain_audio' => '', 'cta' => ''];
        /** @var array<string, mixed> $sequence */
        $sequence = $pack['sequence'] ?? [
            'target_word' => $glyph,
            'syllables' => [['glyph' => $glyph, 'order' => 1]],
            'completion_audio_script' => $isMath
                ? "أحسنت! تعلمت العدد {$glyph}"
                : "أحسنت! تعلمت حرف {$glyph}",
        ];

        $positionIds = $isMath
            ? ['fingers', 'dots', 'objects']
            : ['begin', 'middle', 'end'];

        $defaultLabels = $isMath
            ? [
                1 => 'تعرّف على الأعداد',
                2 => 'عدّ بالترتيب',
                3 => 'أشكال العدد',
                4 => 'تتبّع الرقم',
                5 => 'عدّ الأشياء',
                6 => 'المعلم الصغير',
            ]
            : [
                1 => 'الحركات الثلاث',
                2 => 'فرقعة الفقاعات',
                3 => 'مواقع الحرف',
                4 => 'التتبع بالإصبع',
                5 => 'الاستكشاف البيئي',
                6 => 'المعلم الصغير',
            ];

        /** @var array<int, string> $labels */
        $labels = $pack['demo_station_labels'] ?? $defaultLabels;

        return [
            'key' => (string) ($pack['key'] ?? $pack['lesson_key']),
            'lesson_key' => (string) $pack['lesson_key'],
            'title' => (string) $pack['title'],
            'subtitle' => (string) ($pack['subtitle'] ?? ''),
            'quiz_material_title' => (string) $pack['material_title'],
            'subject_code' => (string) ($pack['subject_code'] ?? ($isMath ? 'MATH' : 'AR')),
            'grade_level' => (int) ($pack['grade_level'] ?? 1),
            'audio_slug' => (string) ($pack['audio_slug'] ?? ($isMath ? 'numbers' : 'letter')),
            'content_kind' => $contentKind,
            'demo_station_labels' => $labels,
            'station_copy' => [
                1 => [
                    'instructions' => $isMath
                        ? "استمع لأسماء الأعداد وعدّ الأشياء مع العدد {$glyph}."
                        : "استمع لحركات حرف {$glyph} واختر الكلمة المناسبة.",
                    'sonbol_prompt' => (string) ($hints['1'] ?? (
                        $isMath
                            ? "مرحباً! أنا سنبل. هيا نعد ونتعلم العدد {$glyph}."
                            : "مرحباً! أنا سنبل. هيا نتعلم حرف {$glyph}."
                    )),
                ],
                2 => [
                    'instructions' => $isMath
                        ? 'رتّب الأعداد بالترتيب الصحيح.'
                        : 'افقع المقاطع بالترتيب لتكوين الكلمة.',
                    'sonbol_prompt' => (string) ($hints['2'] ?? (
                        $isMath ? 'عدّ معي بالترتيب، أنا معك!' : 'رتّب المقاطع، أنا معك!'
                    )),
                ],
                3 => [
                    'instructions' => $isMath
                        ? 'طابق العدد مع أشكاله: أصابع، نقاط، وأشياء.'
                        : "اضغط البطاقة لترى أين يختبئ حرف {$glyph}.",
                    'sonbol_prompt' => (string) ($hints['3'] ?? (
                        $isMath
                            ? "كيف يبدو العدد {$glyph} بأصابعك ونقاطك؟"
                            : "أين يظهر حرف {$glyph} في الكلمة؟"
                    )),
                ],
                4 => [
                    'instructions' => (string) ($stroke['label'] ?? (
                        $isMath ? "تتبّع كتابة العدد {$glyph}" : "تتبّع حرف {$glyph}"
                    )),
                    'sonbol_prompt' => (string) ($hints['4'] ?? (
                        $isMath
                            ? "ارسم العدد {$glyph} ببطء ودقة."
                            : "ارسم حرف {$glyph} ببطء ودقة."
                    )),
                ],
                5 => [
                    'instructions' => (string) ($discovery['intro'] ?? ''),
                    'sonbol_prompt' => (string) ($hints['5'] ?? (
                        $isMath ? 'هيا نعد الأشياء من حولنا!' : 'هيا نكتشف الحرف من حولنا!'
                    )),
                ],
                6 => [
                    'instructions' => 'شاهد الشرح ثم انتقل للاختبار.',
                    'sonbol_prompt' => (string) ($hints['6'] ?? 'هيا نراجع معاً كالمعلمين الصغار!'),
                ],
            ],
            'variant_tabs' => array_map(static function (array $tab): array {
                return [
                    'glyph' => (string) $tab['glyph'],
                    'label' => (string) $tab['label'],
                    'audio_script' => (string) ($tab['audio_script'] ?? $tab['glyph']),
                    'cards' => array_map(static function (array $card): array {
                        return [
                            'word' => (string) ($card['word'] ?? ''),
                            'emoji' => (string) ($card['emoji'] ?? ''),
                            'highlight' => (string) ($card['highlight'] ?? ''),
                            'audio_script' => (string) ($card['audio_script'] ?? $card['audio'] ?? ''),
                            'image_path' => $card['image_path'] ?? null,
                        ];
                    }, $tab['cards'] ?? []),
                ];
            }, $tabs),
            'sequence' => [
                'target_word' => (string) $sequence['target_word'],
                'syllables' => array_values(array_map(static fn (array $s): array => [
                    'glyph' => (string) $s['glyph'],
                    'order' => (int) $s['order'],
                ], $sequence['syllables'] ?? [])),
                'completion_audio_script' => (string) ($sequence['completion_audio_script'] ?? ''),
            ],
            'structure' => [
                'mode' => $isMath ? 'number_forms' : 'letter_position',
                'cards' => array_values(array_map(static function (array $position, int $index) use ($positionIds): array {
                    return [
                        'id' => (string) ($position['id'] ?? $positionIds[$index] ?? 'position-'.($index + 1)),
                        'label' => (string) $position['label'],
                        'display_word' => (string) ($position['word'] ?? $position['display_word'] ?? ''),
                        'parts' => $position['parts'] ?? [],
                        'audio_script' => (string) ($position['audio'] ?? $position['audio_script'] ?? ''),
                    ];
                }, $positions, array_keys($positions))),
            ],
            'tracing' => [
                'label' => (string) ($stroke['label'] ?? ''),
                'path' => (string) $stroke['path'],
                'direction' => (string) ($stroke['direction'] ?? ($isMath ? 'digit_stroke' : 'top_to_bottom_arc')),
                'complete_audio' => (string) ($stroke['complete_audio'] ?? 'أحسنت!'),
                'view_box' => (string) ($stroke['view_box'] ?? '0 0 140 140'),
            ],
            'discovery' => [
                'intro' => (string) ($discovery['intro'] ?? ''),
                'story_audio' => (string) ($discovery['story_audio'] ?? ''),
                'items' => array_map(static function (array $item): array {
                    return [
                        'id' => (string) $item['id'],
                        'label' => (string) $item['label'],
                        'emoji' => (string) ($item['emoji'] ?? ''),
                        'correct' => (bool) ($item['correct'] ?? false),
                        'audio_script' => (string) ($item['audio_script'] ?? $item['audio'] ?? ''),
                    ];
                }, $discovery['items'] ?? []),
            ],
            'demo' => [
                'question' => (string) ($demo['question'] ?? ''),
                'parts' => $demo['parts'] ?? [],
                'explain_audio' => (string) ($demo['explain_audio'] ?? ''),
                'cta' => (string) ($demo['cta'] ?? 'جاهز للاختبار!'),
            ],
            'quiz' => $this->normalizeQuiz($pack['quiz'] ?? ['questions' => []]),
            'mascot_hints' => $pack['mascot_hints'] ?? [],
        ];
    }

    /**
     * @param  array{questions?: list<array<string, mixed>>}  $quiz
     * @return array{questions: list<array<string, mixed>>}
     */
    private function normalizeQuiz(array $quiz): array
    {
        $questions = [];

        foreach ($quiz['questions'] ?? [] as $question) {
            if (! is_array($question)) {
                continue;
            }

            $prompt = (string) ($question['prompt'] ?? '');
            if (isset($question['visual_items']) && is_array($question['visual_items'])) {
                $emoji = (string) ($question['visual_items']['emoji'] ?? '');
                $count = (int) ($question['visual_items']['count'] ?? 0);
                if ($emoji !== '' && $count > 0) {
                    $visual = str_repeat($emoji, $count);
                    if (! str_contains($prompt, $emoji)) {
                        $prompt = trim($prompt.' '.$visual);
                    }
                }
            }

            $explanation = (string) ($question['explanation'] ?? '');
            if (isset($question['mascot_hint']) && is_string($question['mascot_hint']) && $question['mascot_hint'] !== '') {
                $hint = $question['mascot_hint'];
                if ($explanation === '') {
                    $explanation = $hint;
                } elseif (! str_contains($explanation, $hint)) {
                    $explanation = $explanation.' — '.$hint;
                }
            }

            $questions[] = [
                'type' => (string) ($question['type'] ?? 'mcq'),
                'prompt' => $prompt,
                'explanation' => $explanation,
                'options' => $question['options'] ?? [],
                'visual_items' => $question['visual_items'] ?? null,
                'comparison' => $question['comparison'] ?? null,
                'mascot_hint' => $question['mascot_hint'] ?? null,
            ];
        }

        return ['questions' => $questions];
    }
}
