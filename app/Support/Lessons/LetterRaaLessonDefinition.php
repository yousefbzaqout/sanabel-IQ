<?php

declare(strict_types=1);

namespace App\Support\Lessons;

final class LetterRaaLessonDefinition
{
    public const KEY = 'letter-raa';

    public const QUIZ_MATERIAL_TITLE = 'الدرس الأول: حرف الراء';

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     subtitle: string,
     *     quiz_material_title: string,
     *     state_labels: array<int, string>,
     *     diacritic_tabs: list<array{glyph: string, label: string, cards: list<array{word: string, emoji: string, audio: string, highlight: string}>}>,
     *     positions: list<array{label: string, word: string, parts: list<array{text: string, highlight: bool}>, audio: string}>,
     *     tracing: array{label: string, path: string, complete_audio: string},
     *     discovery: array{intro: string, items: list<array{id: string, label: string, emoji: string, correct: bool, audio: string}>},
     *     demo: array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string}
     * }
     */
    public static function definition(): array
    {
        return [
            'key' => self::KEY,
            'title' => 'حرف الراء',
            'subtitle' => 'رحلة الشرح والتأسيس',
            'quiz_material_title' => self::QUIZ_MATERIAL_TITLE,
            'state_labels' => [
                1 => 'الحركات الثلاث',
                2 => 'مواقع الحرف',
                3 => 'التتبع بالإصبع',
                4 => 'الاستكشاف البيئي',
                5 => 'المعلم الصغير',
            ],
            'demo_station_labels' => [
                1 => 'الحركات الثلاث',
                2 => 'فرقعة الفقاعات',
                3 => 'مواقع الحرف',
                4 => 'التتبع بالإصبع',
                5 => 'الاستكشاف البيئي',
                6 => 'المعلم الصغير',
            ],
            'diacritic_tabs' => [
                [
                    'glyph' => 'رَ',
                    'label' => 'فتحة',
                    'cards' => [
                        ['word' => 'رَسّام', 'emoji' => '🎨', 'audio' => 'رَ مثل رَسّام', 'highlight' => 'رَ'],
                        ['word' => 'رَايَة', 'emoji' => '🚩', 'audio' => 'رَ مثل رَايَة', 'highlight' => 'رَ'],
                        ['word' => 'رَمَل', 'emoji' => '🏖️', 'audio' => 'رَ مثل رَمَل', 'highlight' => 'رَ'],
                    ],
                ],
                [
                    'glyph' => 'رُ',
                    'label' => 'ضمة',
                    'cards' => [
                        ['word' => 'رُمّان', 'emoji' => '🍎', 'audio' => 'رُ مثل رُمّان', 'highlight' => 'رُ'],
                        ['word' => 'رُكْبَة', 'emoji' => '🦵', 'audio' => 'رُ مثل رُكْبَة', 'highlight' => 'رُ'],
                        ['word' => 'رُوح', 'emoji' => '✨', 'audio' => 'رُ مثل رُوح', 'highlight' => 'رُ'],
                    ],
                ],
                [
                    'glyph' => 'رِ',
                    'label' => 'كسرة',
                    'cards' => [
                        ['word' => 'رِيشَة', 'emoji' => '🪶', 'audio' => 'رِ مثل رِيشَة', 'highlight' => 'رِ'],
                        ['word' => 'رِجْل', 'emoji' => '🦶', 'audio' => 'رِ مثل رِجْل', 'highlight' => 'رِ'],
                        ['word' => 'رِيح', 'emoji' => '🌬️', 'audio' => 'رِ مثل رِيح', 'highlight' => 'رِ'],
                    ],
                ],
            ],
            'positions' => [
                [
                    'label' => 'في البداية',
                    'word' => 'رَايَة',
                    'parts' => [
                        ['text' => 'رَ', 'highlight' => true],
                        ['text' => 'ايَة', 'highlight' => false],
                    ],
                    'audio' => 'رَايَة: حرف الراء في بداية الكلمة',
                ],
                [
                    'label' => 'في الوسط',
                    'word' => 'مَرْكَب',
                    'parts' => [
                        ['text' => 'مَ', 'highlight' => false],
                        ['text' => 'رْ', 'highlight' => true],
                        ['text' => 'كَب', 'highlight' => false],
                    ],
                    'audio' => 'مَرْكَب: حرف الراء في وسط الكلمة',
                ],
                [
                    'label' => 'في النهاية',
                    'word' => 'جَزَر',
                    'parts' => [
                        ['text' => 'جَزَ', 'highlight' => false],
                        ['text' => 'ر', 'highlight' => true],
                    ],
                    'audio' => 'جَزَر: حرف الراء في نهاية الكلمة',
                ],
            ],
            'tracing' => [
                'label' => 'تتبّع حرف الراء بإصبعك',
                'path' => 'M 70 28 C 92 28 108 48 108 72 C 108 96 92 112 70 112 C 48 112 36 96 36 78',
                'complete_audio' => 'أحسنت! لقد رسمت حرف الراء يا بطل!',
            ],
            'discovery' => [
                'intro' => 'ابحث عن الأشياء التي فيها حرف الراء في البيارة',
                'items' => [
                    ['id' => 'rumman', 'label' => 'رُمّان', 'emoji' => '🍎', 'correct' => true, 'audio' => 'رُمّان فيها حرف الراء'],
                    ['id' => 'zaytoun', 'label' => 'زَيْتُون', 'emoji' => '🫒', 'correct' => false, 'audio' => 'زَيْتُون ليس فيها راء'],
                    ['id' => 'reesha', 'label' => 'رِيشَة', 'emoji' => '🪶', 'correct' => true, 'audio' => 'رِيشَة فيها حرف الراء'],
                    ['id' => 'toofaah', 'label' => 'تُفّاح', 'emoji' => '🍏', 'correct' => false, 'audio' => 'تُفّاح ليس فيها راء'],
                    ['id' => 'rayah', 'label' => 'رَايَة', 'emoji' => '🚩', 'correct' => true, 'audio' => 'رَايَة فيها حرف الراء'],
                ],
            ],
            'demo' => [
                'question' => 'أين حرف الراء في كلمة (مَرْكَب)؟',
                'parts' => [
                    ['text' => 'مَ', 'highlight' => false],
                    ['text' => 'رْ', 'highlight' => true],
                    ['text' => 'كَب', 'highlight' => false],
                ],
                'explain_audio' => 'انظر معي: في كلمة مَرْكَب، حرف الراء في الوسط. مَ ـ رْ ـ كَب. أحسنت المتابعة!',
                'cta' => 'جاهز للاختبار يا بطل! 🎯',
            ],
        ];
    }
}
