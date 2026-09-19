<?php

declare(strict_types=1);

namespace App\Support\Lessons;

final class PalestineFlagLessonDefinition
{
    public const KEY = 'civics-palestine-flag';

    public const LESSON_KEY = 'ar-g1-civics-palestine-flag';

    public const QUIZ_MATERIAL_TITLE = 'علم بلادي فلسطين';

    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'key' => self::KEY,
            'lesson_key' => self::LESSON_KEY,
            'title' => 'علم بلادي فلسطين',
            'subtitle' => 'نحب علمنا ونعرف ألوانه',
            'quiz_material_title' => self::QUIZ_MATERIAL_TITLE,
            'subject_code' => 'SOCIAL',
            'audio_slug' => 'palestine-flag',
            'demo_station_labels' => [
                1 => 'ألوان العلم',
                2 => 'رتّب الألوان',
                3 => 'أجزاء العلم',
                4 => 'ارسم المثلث',
                5 => 'اكتشف رموز الوطن',
                6 => 'المعلم الصغير',
            ],
            'station_copy' => [
                1 => [
                    'instructions' => 'تعرّف على ألوان علم فلسطين.',
                    'sonbol_prompt' => 'علم بلادي فيه أسود وأبيض وأخضر وأحمر!',
                ],
                2 => [
                    'instructions' => 'افقع الألوان بالترتيب من الأعلى: أسود ثم أبيض ثم أخضر.',
                    'sonbol_prompt' => 'هيا نرتّب أشرطة العلم معاً!',
                ],
                3 => [
                    'instructions' => 'اضغط البطاقة لترى أجزاء العلم.',
                    'sonbol_prompt' => 'المثلث الأحمر جزء مهم من علم فلسطين!',
                ],
                4 => [
                    'instructions' => 'تتبّع المثلث الأحمر في العلم.',
                    'sonbol_prompt' => 'ارسم المثلث من اليسار بهدوء.',
                ],
                5 => [
                    'instructions' => 'امسح الرمل واعثر على رموز فلسطين.',
                    'sonbol_prompt' => 'مرحباً! هذه أرض الزيتون والعلم الفلسطيني!',
                ],
                6 => [
                    'instructions' => 'شاهد الشرح ثم انتقل لاختبار الوطن.',
                    'sonbol_prompt' => 'هيا نراجع علم بلادي كالمعلمين الصغار!',
                ],
            ],
            'variant_tabs' => [
                [
                    'glyph' => '⬛',
                    'label' => 'أسود',
                    'audio_script' => 'اللون الأسود',
                    'cards' => [
                        ['word' => 'أسود', 'emoji' => '⬛', 'highlight' => 'أسود', 'audio_script' => 'الشريط الأسود'],
                        ['word' => 'أبيض', 'emoji' => '⬜', 'highlight' => 'أبيض', 'audio_script' => 'الشريط الأبيض'],
                        ['word' => 'أخضر', 'emoji' => '🟩', 'highlight' => 'أخضر', 'audio_script' => 'الشريط الأخضر'],
                    ],
                ],
                [
                    'glyph' => '🔺',
                    'label' => 'أحمر',
                    'audio_script' => 'المثلث الأحمر',
                    'cards' => [
                        ['word' => 'أحمر', 'emoji' => '🔺', 'highlight' => 'أحمر', 'audio_script' => 'المثلث الأحمر'],
                        ['word' => 'علم', 'emoji' => '🇵🇸', 'highlight' => 'علم', 'audio_script' => 'علم فلسطين'],
                        ['word' => 'وطني', 'emoji' => '❤️', 'highlight' => 'وطني', 'audio_script' => 'أحب وطني فلسطين'],
                    ],
                ],
            ],
            'sequence' => [
                'target_word' => 'أسودأبيضأخضر',
                'syllables' => [
                    ['glyph' => '⬛', 'order' => 1],
                    ['glyph' => '⬜', 'order' => 2],
                    ['glyph' => '🟩', 'order' => 3],
                ],
                'completion_audio_script' => 'أحسنت! رتّبت أشرطة العلم: أسود، أبيض، أخضر!',
            ],
            'structure' => [
                'mode' => 'flag_parts',
                'cards' => [
                    [
                        'id' => 'black',
                        'label' => 'الشريط الأسود',
                        'display_word' => '⬛ أسود',
                        'parts' => [
                            ['text' => '⬛', 'highlight' => true],
                            ['text' => ' أعلى', 'highlight' => false],
                        ],
                        'audio_script' => 'الشريط الأسود في الأعلى',
                    ],
                    [
                        'id' => 'white',
                        'label' => 'الشريط الأبيض',
                        'display_word' => '⬜ أبيض',
                        'parts' => [
                            ['text' => '⬜', 'highlight' => true],
                            ['text' => ' وسط', 'highlight' => false],
                        ],
                        'audio_script' => 'الشريط الأبيض في الوسط',
                    ],
                    [
                        'id' => 'triangle',
                        'label' => 'المثلث الأحمر',
                        'display_word' => '🔺 أحمر',
                        'parts' => [
                            ['text' => '🔺', 'highlight' => true],
                            ['text' => ' جانب', 'highlight' => false],
                        ],
                        'audio_script' => 'المثلث الأحمر على جانب العلم',
                    ],
                ],
            ],
            'tracing' => [
                'label' => 'تتبّع المثلث الأحمر في العلم',
                'path' => 'M 30 30 L 95 70 L 30 110 Z',
                'direction' => 'loop',
                'complete_audio' => 'أحسنت! رسمت مثلث العلم يا بطل!',
            ],
            'discovery' => [
                'intro' => 'امسح الرمل واعثر على رموز فلسطين الحبيبة',
                'story_audio' => 'مرحباً! هذه أرض الزيتون. انظر… علم فلسطين يرفرف عالياً!',
                'items' => [
                    ['id' => 'flag', 'label' => 'العلم', 'emoji' => '🇵🇸', 'correct' => true, 'audio_script' => 'هذا علم فلسطين'],
                    ['id' => 'olive', 'label' => 'زيتون', 'emoji' => '🫒', 'correct' => true, 'audio_script' => 'الزيتون من خيرات فلسطين'],
                    ['id' => 'key', 'label' => 'مفتاح', 'emoji' => '🗝️', 'correct' => true, 'audio_script' => 'مفتاح العودة رمز مهم'],
                    ['id' => 'banana', 'label' => 'موزة', 'emoji' => '🍌', 'correct' => false, 'audio_script' => 'الموزة ليست رمز العلم'],
                    ['id' => 'robot', 'label' => 'روبوت', 'emoji' => '🤖', 'correct' => false, 'audio_script' => 'الروبوت ليس من رموز الوطن'],
                ],
            ],
            'demo' => [
                'question' => 'ما اسم وطننا الحبيب؟',
                'parts' => [
                    ['text' => 'فلسطين', 'highlight' => true],
                    ['text' => ' 🇵🇸', 'highlight' => false],
                ],
                'explain_audio' => 'انظر معي: وطننا هو فلسطين، وعلمنا أسود وأبيض وأخضر مع مثلث أحمر. أحسنت!',
                'cta' => 'جاهز لاختبار علم بلادي يا بطل! 🎯',
            ],
        ];
    }
}
