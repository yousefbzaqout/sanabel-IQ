<?php

declare(strict_types=1);

namespace App\Support\Lessons;

final class SurahFatihaLessonDefinition
{
    public const KEY = 'islamic-surah-fatiha';

    public const LESSON_KEY = 'ar-g1-islamic-surah-fatiha';

    public const QUIZ_MATERIAL_TITLE = 'سورة الفاتحة';

    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'key' => self::KEY,
            'lesson_key' => self::LESSON_KEY,
            'title' => 'سورة الفاتحة',
            'subtitle' => 'نتعلم فاتحة الكتاب مع سنبل',
            'quiz_material_title' => self::QUIZ_MATERIAL_TITLE,
            'subject_code' => 'ISLAM',
            'audio_slug' => 'surah-fatiha',
            'demo_station_labels' => [
                1 => 'البسملة',
                2 => 'رتّب الآيات',
                3 => 'معاني الفاتحة',
                4 => 'اكتب الباء',
                5 => 'اكتشف المسجد',
                6 => 'المعلم الصغير',
            ],
            'station_copy' => [
                1 => [
                    'instructions' => 'استمع لكلمات البسملة الجميلة.',
                    'sonbol_prompt' => 'هيا نبدأ بـ بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ!',
                ],
                2 => [
                    'instructions' => 'افقع الكلمات بالترتيب: الْحَمْدُ ثم لِلَّهِ ثم رَبِّ.',
                    'sonbol_prompt' => 'هيا نرتّب بداية سورة الفاتحة معاً!',
                ],
                3 => [
                    'instructions' => 'اضغط البطاقة لتعرف معنى من معاني الفاتحة.',
                    'sonbol_prompt' => 'الفاتحة علّمتنا الحمد والرحمة والعبادة!',
                ],
                4 => [
                    'instructions' => 'تتبّع حرف الباء من البسملة.',
                    'sonbol_prompt' => 'ارسم حرف الباء بهدوء من اليمين.',
                ],
                5 => [
                    'instructions' => 'امسح الرمل واعثر على ما يتعلق بالصلاة.',
                    'sonbol_prompt' => 'في المسجد نقرأ الفاتحة في كل صلاة!',
                ],
                6 => [
                    'instructions' => 'شاهد كيف يشرح سنبل ثم انتقل للاختبار.',
                    'sonbol_prompt' => 'هيا نراجع سورة الفاتحة كالمعلمين الصغار!',
                ],
            ],
            'variant_tabs' => [
                [
                    'glyph' => 'بِسْمِ',
                    'label' => 'البسملة',
                    'audio_script' => 'بِسْمِ اللَّهِ',
                    'cards' => [
                        ['word' => 'بِسْمِ', 'emoji' => '📖', 'highlight' => 'بِسْمِ', 'audio_script' => 'بِسْمِ'],
                        ['word' => 'اللَّهِ', 'emoji' => '✨', 'highlight' => 'اللَّهِ', 'audio_script' => 'اللَّهِ'],
                        ['word' => 'الرَّحْمَٰنِ', 'emoji' => '💚', 'highlight' => 'الرَّحْمَٰنِ', 'audio_script' => 'الرَّحْمَٰنِ'],
                    ],
                ],
                [
                    'glyph' => 'الرَّحِيمِ',
                    'label' => 'الرحيم',
                    'audio_script' => 'الرَّحِيمِ',
                    'cards' => [
                        ['word' => 'الرَّحِيمِ', 'emoji' => '🤍', 'highlight' => 'الرَّحِيمِ', 'audio_script' => 'الرَّحِيمِ'],
                        ['word' => 'رحمة', 'emoji' => '🤲', 'highlight' => 'رحمة', 'audio_script' => 'رحمة من الله'],
                        ['word' => 'فاتحة', 'emoji' => '🕌', 'highlight' => 'فاتحة', 'audio_script' => 'سورة الفاتحة'],
                    ],
                ],
            ],
            'sequence' => [
                'target_word' => 'الْحَمْدُلِلَّهِرَبِّ',
                'syllables' => [
                    ['glyph' => 'الْحَمْدُ', 'order' => 1],
                    ['glyph' => 'لِلَّهِ', 'order' => 2],
                    ['glyph' => 'رَبِّ', 'order' => 3],
                ],
                'completion_audio_script' => 'أحسنت! الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ',
            ],
            'structure' => [
                'mode' => 'meaning_cards',
                'cards' => [
                    [
                        'id' => 'hamd',
                        'label' => 'الحمد',
                        'display_word' => 'الْحَمْدُ',
                        'parts' => [
                            ['text' => 'الْحَمْدُ', 'highlight' => true],
                            ['text' => ' = شكر', 'highlight' => false],
                        ],
                        'audio_script' => 'الحمد يعني الشكر لله',
                    ],
                    [
                        'id' => 'rahma',
                        'label' => 'الرحمة',
                        'display_word' => 'الرَّحْمَٰنِ',
                        'parts' => [
                            ['text' => 'الرَّحْمَٰنِ', 'highlight' => true],
                            ['text' => ' = رحمة', 'highlight' => false],
                        ],
                        'audio_script' => 'الرحمن كثير الرحمة',
                    ],
                    [
                        'id' => 'ibada',
                        'label' => 'العبادة',
                        'display_word' => 'نَعْبُدُ',
                        'parts' => [
                            ['text' => 'إِيَّاكَ', 'highlight' => false],
                            ['text' => ' نَعْبُدُ', 'highlight' => true],
                        ],
                        'audio_script' => 'نعبد الله وحده',
                    ],
                ],
            ],
            'tracing' => [
                'label' => 'تتبّع حرف الباء من البسملة',
                'path' => 'M 110 40 L 40 40 L 40 95 C 40 115 55 120 70 110',
                'direction' => 'top_to_bottom_arc',
                'complete_audio' => 'أحسنت! كتبت حرف الباء يا بطل!',
            ],
            'discovery' => [
                'intro' => 'امسح الرمل واعثر على ما يذكّرنا بالصلاة والفاتحة',
                'story_audio' => 'مرحباً! في المسجد نقرأ سورة الفاتحة في كل صلاة.',
                'items' => [
                    ['id' => 'masjid', 'label' => 'مسجد', 'emoji' => '🕌', 'correct' => true, 'audio_script' => 'المسجد بيت الصلاة'],
                    ['id' => 'mushaf', 'label' => 'مصحف', 'emoji' => '📖', 'correct' => true, 'audio_script' => 'المصحف فيه سورة الفاتحة'],
                    ['id' => 'prayer', 'label' => 'صلاة', 'emoji' => '🤲', 'correct' => true, 'audio_script' => 'نقرأ الفاتحة في الصلاة'],
                    ['id' => 'ball', 'label' => 'كرة', 'emoji' => '⚽', 'correct' => false, 'audio_script' => 'الكرة ليست من أدوات الصلاة'],
                    ['id' => 'car', 'label' => 'سيارة', 'emoji' => '🚗', 'correct' => false, 'audio_script' => 'السيارة ليست من الصلاة'],
                ],
            ],
            'demo' => [
                'question' => 'بمَ نبدأ قراءة القرآن؟',
                'parts' => [
                    ['text' => 'بِسْمِ', 'highlight' => true],
                    ['text' => ' اللَّهِ', 'highlight' => false],
                ],
                'explain_audio' => 'انظر معي: نبدأ بـ بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ. أحسنت المتابعة!',
                'cta' => 'جاهز لاختبار سورة الفاتحة يا بطل! 🎯',
            ],
        ];
    }
}
