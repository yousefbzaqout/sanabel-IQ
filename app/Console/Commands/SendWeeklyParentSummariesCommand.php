<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WeeklyParentEncouragementNotification;
use App\Notifications\WeeklyParentSummaryNotification;
use App\Notifications\WeeklySummaryWebPushNotification;
use App\Services\Notifications\WeeklyParentSummaryService;
use Illuminate\Console\Command;

class SendWeeklyParentSummariesCommand extends Command
{
    protected $signature = 'sanabel:send-weekly-summaries';

    protected $description = 'Send weekly progress summary notifications to parents with active students.';

    public function handle(WeeklyParentSummaryService $summaryService): int
    {
        $activeSummaryCount = 0;
        $encouragementCount = 0;

        User::query()
            ->whereHas('students')
            ->with('students')
            ->orderBy('id')
            ->chunkById(100, function ($parents) use ($summaryService, &$activeSummaryCount, &$encouragementCount): void {
                foreach ($parents as $parent) {
                    if ($parent->students->isEmpty()) {
                        continue;
                    }

                    if ($summaryService->parentHasWeeklyActivity($parent)) {
                        $summary = $summaryService->buildForParent($parent);
                        $parent->notify(new WeeklyParentSummaryNotification($summary));
                        $parent->notify(new WeeklySummaryWebPushNotification($summary));
                        $activeSummaryCount++;

                        continue;
                    }

                    $encouragementSummary = $summaryService->buildEncouragementForParent($parent);
                    $parent->notify(new WeeklyParentEncouragementNotification($encouragementSummary));
                    $encouragementCount++;
                }
            });

        $this->info("Dispatched {$activeSummaryCount} active weekly summaries and {$encouragementCount} encouragement notifications.");

        return self::SUCCESS;
    }
}
