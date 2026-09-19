<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\InteractiveLesson;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Support\Tenancy\FilamentTenantSynchronizer;
use App\Support\Tenancy\TenantContext;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantOverviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    /**
     * @return array{students: int, active_lessons: int, analytics_events: int}
     */
    public function getStatsForTesting(): array
    {
        FilamentTenantSynchronizer::syncFromAuth();

        return $this->resolveCounts();
    }

    protected function getStats(): array
    {
        FilamentTenantSynchronizer::syncFromAuth();

        $counts = $this->resolveCounts();
        $tenant = TenantContext::tenant();

        $studentStat = Stat::make('الطلاب', (string) $counts['students']);

        if ($tenant !== null) {
            $limit = $tenant->seatLimit();
            $remaining = $tenant->remainingSeats();

            if ($remaining === 0) {
                $studentStat->description("تم استنفاد السعة ({$counts['students']} / {$limit})")
                    ->color('danger');
            } else {
                $studentStat->description("المقاعد المتبقية: {$remaining} من {$limit}")
                    ->color('success');
            }
        } else {
            $studentStat->description('في المدرسة الحالية');
        }

        return [
            $studentStat,
            Stat::make('الدروس النشطة', (string) $counts['active_lessons'])
                ->description('دروس منشورة'),
            Stat::make('أحداث التحليلات', (string) $counts['analytics_events'])
                ->description('سجلات Lesson Analytics'),
        ];
    }

    /**
     * @return array{students: int, active_lessons: int, analytics_events: int}
     */
    private function resolveCounts(): array
    {
        $tenantId = TenantContext::id();

        return [
            'students' => Student::query()
                ->whereHas('user', function ($query) use ($tenantId): void {
                    $query->withoutGlobalScope('tenant');

                    if ($tenantId !== null) {
                        $query->where('tenant_id', $tenantId);
                    }
                })
                ->count(),
            'active_lessons' => InteractiveLesson::query()
                ->where('status', 'published')
                ->count(),
            'analytics_events' => LessonAnalytic::query()->count(),
        ];
    }
}
