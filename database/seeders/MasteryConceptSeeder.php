<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MasteryConcept;
use Illuminate\Database\Seeder;

class MasteryConceptSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = [
            [
                'code' => 'diacritic_confusion',
                'label' => 'خلط الحركات',
                'hint_template' => 'تلميح سنبل: فرّق جيداً بين رَ (فتحة) و رُ (ضمة). افتح فمك قليلاً مع رَ!',
                'threshold' => 2,
                'meta' => [
                    'station' => 1,
                    'lesson_overrides' => [
                        'ar-g1-letter-raa' => [
                            'hint' => 'تلميح سنبل: فرّق جيداً بين رَ (فتحة) و رُ (ضمة). افتح فمك قليلاً مع رَ!',
                        ],
                        'ar-g1-math-number-3' => [
                            'hint' => 'تلميح سنبل: ركّز على صوت العدد 3 وعدّ التفاحات ببطء: واحدة، اثنان، ثلاثة.',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'bubble_sequence',
                'label' => 'ترتيب التسلسل',
                'hint_template' => 'تلميح سنبل: رتّب الفقاعات هكذا — رَ ثم مَ ثم لْ لتكوّن رَمَل.',
                'threshold' => 2,
                'meta' => [
                    'station' => 2,
                    'lesson_overrides' => [
                        'ar-g1-letter-raa' => [
                            'hint' => 'تلميح سنبل: رتّب الفقاعات هكذا — رَ ثم مَ ثم لْ لتكوّن رَمَل.',
                        ],
                        'ar-g1-math-number-3' => [
                            'hint' => 'تلميح سنبل: عدّ بالترتيب 1 ثم 2 ثم 3 — لا تتخطَّ أي رقم!',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'incomplete_trace',
                'label' => 'تتبع غير مكتمل',
                'hint_template' => 'تلميح سنبل: ابدأ من أعلى حرف الراء وانزل بالقوس بهدوء حتى النهاية.',
                'threshold' => 2,
                'meta' => [
                    'station' => 4,
                    'lesson_overrides' => [
                        'ar-g1-letter-raa' => [
                            'hint' => 'تلميح سنبل: ابدأ من أعلى حرف الراء وانزل بالقوس بهدوء حتى النهاية.',
                        ],
                        'ar-g1-math-number-3' => [
                            'hint' => 'تلميح سنبل: اكتب العدد 3 من الأعلى: قوس علوي ثم قوس سفلي بهدوء.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($concepts as $concept) {
            MasteryConcept::query()->updateOrCreate(
                ['code' => $concept['code']],
                [
                    'label' => $concept['label'],
                    'hint_template' => $concept['hint_template'],
                    'threshold' => $concept['threshold'],
                    'meta' => $concept['meta'],
                ],
            );
        }
    }
}
