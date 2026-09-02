<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Services\Analytics\SubjectAnalyticsService;
use App\Support\ActiveChildResolver;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChildOverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());

        if ($student === null) {
            return [
                Stat::make('لا يوجد ابن نشط', '—'),
            ];
        }

        $analysis = app(SubjectAnalyticsService::class)->analyze($student);
        $streakDays = (int) ($student->streak?->current_streak ?? 0);
        $quizCount = (int) $student->quizAttempts()->distinct()->count('learning_material_id');

        return [
            Stat::make('XP', (string) $student->total_xp)
                ->description('إجمالي النقاط'),
            Stat::make('الدقة', $analysis['overall_accuracy_percent'].'%')
                ->description('نسبة الإجابات الصحيحة'),
            Stat::make('السلسلة', $streakDays.' 🔥')
                ->description('أيام متتالية'),
            Stat::make('الاختبارات', (string) $quizCount)
                ->description('اختبارات مكتملة'),
        ];
    }
}
