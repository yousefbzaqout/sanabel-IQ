<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WeeklyParentSummaryNotification;
use App\Services\Notifications\WeeklyParentSummaryService;
use Illuminate\Console\Command;

class SendWeeklyParentSummariesCommand extends Command
{
    protected $signature = 'sanabel:send-weekly-summaries';

    protected $description = 'Send weekly progress summary notifications to parents with active students.';

    public function handle(WeeklyParentSummaryService $summaryService): int
    {
        $sentCount = 0;

        User::query()
            ->whereHas('students')
            ->with('students')
            ->orderBy('id')
            ->chunkById(100, function ($parents) use ($summaryService, &$sentCount): void {
                foreach ($parents as $parent) {
                    $summary = $summaryService->buildForParent($parent);
                    $parent->notify(new WeeklyParentSummaryNotification($summary));
                    $sentCount++;
                }
            });

        $this->info("Dispatched {$sentCount} weekly parent summary notifications.");

        return self::SUCCESS;
    }
}
