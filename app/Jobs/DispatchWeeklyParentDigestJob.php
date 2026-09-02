<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\WeeklySummaryMailable;
use App\Models\ParentReportLog;
use App\Models\User;
use App\Notifications\WeeklySummaryWebPushNotification;
use App\Services\Analytics\ParentAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class DispatchWeeklyParentDigestJob implements ShouldQueue
{
    use Queueable;

    public function handle(ParentAnalyticsService $analyticsService): void
    {
        $periodStart = now()->copy()->subDays(7)->startOfDay();
        $periodEnd = now()->copy()->endOfDay();

        User::query()
            ->whereHas('students')
            ->with('students')
            ->orderBy('id')
            ->chunkById(100, function ($parents) use ($analyticsService, $periodStart, $periodEnd): void {
                foreach ($parents as $parent) {
                    if ($this->digestAlreadySent($parent, $periodStart)) {
                        continue;
                    }

                    $children = [];

                    foreach ($parent->students as $student) {
                        $summary = $analyticsService->buildRollingSummary($student);

                        $children[] = [
                            'student_id' => $student->id,
                            'name' => $student->name,
                            'grade_level' => $student->grade_level,
                            'xp_earned' => $summary->xpEarnedInPeriod,
                            'quiz_accuracy_percent' => $summary->quizAccuracyPercent,
                            'activities_completed' => $summary->activitiesCompleted,
                            'analytics_url' => route('parent.analytics.show', $student),
                        ];
                    }

                    if ($children === []) {
                        continue;
                    }

                    $digest = [
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'children' => $children,
                    ];

                    Mail::to($parent)->queue(new WeeklySummaryMailable($parent, $digest));
                    $parent->notify(new WeeklySummaryWebPushNotification($digest));

                    foreach ($parent->students as $student) {
                        ParentReportLog::query()->create([
                            'parent_id' => $parent->id,
                            'student_id' => $student->id,
                            'report_type' => 'weekly_digest',
                            'start_date' => $periodStart->toDateString(),
                            'end_date' => $periodEnd->toDateString(),
                            'file_path' => null,
                        ]);
                    }
                }
            });
    }

    private function digestAlreadySent(User $parent, Carbon $periodStart): bool
    {
        return ParentReportLog::query()
            ->where('parent_id', $parent->id)
            ->where('report_type', 'weekly_digest')
            ->whereDate('start_date', $periodStart->toDateString())
            ->exists();
    }
}
