<?php

declare(strict_types=1);

namespace App\Support\Lessons;

final class NumberThreeLessonDefinition
{
    public const KEY = 'math-number-3';

    public const LESSON_KEY = 'ar-g1-math-number-3';

    public const QUIZ_MATERIAL_TITLE = 'العدد 3';

    /**
     * @return array{
     *     key: string,
     *     lesson_key: string,
     *     title: string,
     *     subtitle: string,
     *     quiz_material_title: string,
     *     subject_code: string,
     *     demo_station_labels: array<int, string>,
     *     variant_tabs: list<array<string, mixed>>,
     *     sequence: array{target_word: string, syllables: list<array{glyph: string, order: int}>, completion_audio_script: string},
     *     structure_cards: list<array<string, mixed>>,
     *     tracing: array{label: string, path: string, complete_audio: string},
     *     discovery: array{intro: string, story_audio: string, items: list<array<string, mixed>>},
     *     demo: array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string}
     * }
     */
    public static function definition(): array
    {
        return [
            'key' => self::KEY,
            'lesson_key' => self::LESSON_KEY,
            'title' => 'العدد 3',
            'subtitle' => 'نعد ونكتب ونكتشف الثلاثة',
            'quiz_material_title' => self::QUIZ_MATERIAL_TITLE,
            'subject_code' => 'MATH',
            'demo_station_labels' => [
                1 => 'تعرّف على العدد 3',
                2 => 'عدّ بالترتيب',
                3 => 'أشكال العدد 3',
                4 => 'اكتب العدد 3',
                5 => 'اكتشف العنب',
                6 => 'المعلم الصغير',
            ],
            'variant_tabs' => [
                [
                    'glyph' => '3',
                    'label' => 'العدد ثلاثة',
                    'audio_script' => 'هذا العدد ثلاثة',
                    'audio_path' => 'audio/grade1/lessons/number-3/three.mp3',
                    'cards' => [
                        [
                            'word' => '٣ تفاح',
                            'emoji' => '🍎🍎🍎',
                            'highlight' => '٣',
                            'audio_script' => 'ثلاث تفاحات',
                            'audio_path' => 'audio/grade1/lessons/number-3/three-apples.mp3',
                            'image_path' => null,
                        ],
                        [
                            'word' => 'ثلاثة',
                            'emoji' => '3️⃣',
                            'highlight' => 'ثلاثة',
                            'audio_script' => 'نقول ثلاثة',
                            'audio_path' => 'audio/grade1/lessons/number-3/thalatha.mp3',
                            'image_path' => null,
                        ],
                        [
                            'word' => '3',
                            'emoji' => '🔢',
                            'highlight' => '3',
                            'audio_script' => 'نكتب الرقم 3',
                            'audio_path' => 'audio/grade1/lessons/number-3/digit-3.mp3',
                            'image_path' => null,
                        ],
                    ],
                ],
            ],
            'sequence' => [
                'target_word' => '123',
                'syllables' => [
                    ['glyph' => '1', 'order' => 1],
                    ['glyph' => '2', 'order' => 2],
                    ['glyph' => '3', 'order' => 3],
                ],
                'completion_audio_script' => 'أحسنت! عددت واحد، اثنان، ثلاثة!',
            ],
            'structure_cards' => [
                [
                    'id' => 'fingers',
                    'label' => 'ثلاثة أصابع',
                    'display_word' => '🖐️🖐️🖐️',
                    'parts' => [
                        ['text' => '🖐️', 'highlight' => true],
                        ['text' => '🖐️', 'highlight' => true],
                        ['text' => '🖐️', 'highlight' => true],
                    ],
                    'audio_script' => 'ثلاث أصابع مثل العدد 3',
                ],
                [
                    'id' => 'dots',
                    'label' => 'ثلاث نقاط',
                    'display_word' => '🎲🎲🎲',
                    'parts' => [
                        ['text' => '●', 'highlight' => true],
                        ['text' => '●', 'highlight' => true],
                        ['text' => '●', 'highlight' => true],
                    ],
                    'audio_script' => 'ثلاث نقاط على حجر النرد',
                ],
                [
                    'id' => 'balls',
                    'label' => 'ثلاث كرات',
                    'display_word' => '⚽⚽⚽',
                    'parts' => [
                        ['text' => '⚽', 'highlight' => true],
                        ['text' => '⚽', 'highlight' => true],
                        ['text' => '⚽', 'highlight' => true],
                    ],
                    'audio_script' => 'ثلاث كرات جميلة',
                ],
            ],
            'tracing' => [
                'label' => 'تتبّع كتابة العدد 3 بإصبعك',
                // Two-curve digit 3 guide within 140×140 viewBox.
                'path' => 'M 48 34 C 78 28 108 38 108 58 C 108 74 88 78 70 78 C 92 78 112 88 112 108 C 112 126 86 132 52 126',
                'complete_audio' => 'أحسنت! لقد كتبت العدد 3 يا بطل!',
            ],
            'discovery' => [
                'intro' => 'امسح الرمل واعثر على ثلاث عناقيد عنب في البستان',
                'story_audio' => 'مرحباً! في بستان العنب يوجد عنب كثير. هيا نعد ثلاثة عناقيد عنب!',
                'items' => [
                    ['id' => 'grape-1', 'label' => 'عنب ١', 'emoji' => '🍇', 'correct' => true, 'audio_script' => 'عنقود عنب واحد'],
                    ['id' => 'grape-2', 'label' => 'عنب ٢', 'emoji' => '🍇', 'correct' => true, 'audio_script' => 'عنقود عنب اثنان'],
                    ['id' => 'grape-3', 'label' => 'عنب ٣', 'emoji' => '🍇', 'correct' => true, 'audio_script' => 'عنقود عنب ثلاثة'],
                    ['id' => 'apple', 'label' => 'تفاحة', 'emoji' => '🍎', 'correct' => false, 'audio_script' => 'هذه تفاحة وليست عنباً'],
                    ['id' => 'leaf', 'label' => 'ورقة', 'emoji' => '🍃', 'correct' => false, 'audio_script' => 'هذه ورقة خضراء'],
                ],
            ],
            'demo' => [
                'question' => 'كم عنقود عنب هنا؟ 🍇🍇🍇',
                'parts' => [
                    ['text' => '🍇', 'highlight' => true],
                    ['text' => '🍇', 'highlight' => true],
                    ['text' => '🍇', 'highlight' => true],
                    ['text' => ' = 3', 'highlight' => false],
                ],
                'explain_audio' => 'انظر معي: عنقود، عنقودان، ثلاثة. الجواب هو 3!',
                'cta' => 'جاهز لاختبار العدد 3 يا بطل! 🎯',
            ],
        ];
    }
}
