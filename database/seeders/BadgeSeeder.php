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
                'slug' => 'first_activity',
                'name' => 'البداية المشرقة',
                'description' => 'اكتمال أول نشاط',
                'icon' => 'sparkles',
                'requirement_type' => 'activities_completed',
                'requirement_value' => 1,
            ],
            [
                'slug' => 'xp_100',
                'name' => 'المستكشف',
                'description' => 'الحصول على 100 نقطة خبرة',
                'icon' => 'compass',
                'requirement_type' => 'xp_threshold',
                'requirement_value' => 100,
            ],
            [
                'slug' => 'perfect_score',
                'name' => 'العلامة الكاملة',
                'description' => 'تحقيق 100% في نشاط',
                'icon' => 'trophy',
                'requirement_type' => 'perfect_scores',
                'requirement_value' => 1,
            ],
            [
                'slug' => 'master_5',
                'name' => 'المثابر',
                'description' => 'إكمال 5 أنشطة بنجاح',
                'icon' => 'medal',
                'requirement_type' => 'activities_completed',
                'requirement_value' => 5,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::query()->updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
