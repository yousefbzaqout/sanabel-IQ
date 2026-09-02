<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'code' => 'first_quiz',
                'name_ar' => 'أول خطوة',
                'description_ar' => 'إكمال أول اختبار',
                'icon' => 'footprints',
                'criteria_type' => 'quiz_count',
                'criteria_value' => 1,
            ],
            [
                'code' => 'first_activity',
                'name_ar' => 'البداية المشرقة',
                'description_ar' => 'اكتمال أول نشاط',
                'icon' => 'sparkles',
                'criteria_type' => 'activities_completed',
                'criteria_value' => 1,
            ],
            [
                'code' => 'xp_100',
                'name_ar' => 'المستكشف',
                'description_ar' => 'الحصول على 100 نقطة خبرة',
                'icon' => 'compass',
                'criteria_type' => 'xp_threshold',
                'criteria_value' => 100,
            ],
            [
                'code' => 'perfect_score',
                'name_ar' => 'العلامة الكاملة',
                'description_ar' => 'تحقيق 100% في نشاط أو اختبار',
                'icon' => 'trophy',
                'criteria_type' => 'perfect_scores',
                'criteria_value' => 1,
            ],
            [
                'code' => 'master_5',
                'name_ar' => 'المثابر',
                'description_ar' => 'إكمال 5 أنشطة بنجاح',
                'icon' => 'medal',
                'criteria_type' => 'activities_completed',
                'criteria_value' => 5,
            ],
            [
                'code' => 'streak_7',
                'name_ar' => 'سلسلة 7 أيام',
                'description_ar' => 'الحفاظ على نشاط يومي لمدة 7 أيام',
                'icon' => 'flame',
                'criteria_type' => 'streak_days',
                'criteria_value' => 7,
            ],
            [
                'code' => 'math_master',
                'name_ar' => 'بطل الرياضيات',
                'description_ar' => 'إكمال 10 اختبارات',
                'icon' => 'calculator',
                'criteria_type' => 'quiz_count',
                'criteria_value' => 10,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::query()->updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
