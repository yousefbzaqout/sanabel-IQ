<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\WeeklySummaryMailable;
use App\Models\ParentReportLog;
use App\Models\Student;
use App\Models\User;
use App\Notifications\WeeklySummaryWebPushNotification;
use App\Services\Analytics\ParentAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
            ->chunkById(100, function (Collection $parents) use ($analyticsService, $periodStart, $periodEnd): void {
                foreach ($parents as $parent) {
                    $this->dispatchDigestForParent($parent, $analyticsService, $periodStart, $periodEnd);
                }

                unset($parents);

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            });
    }

    private function dispatchDigestForParent(
        User $parent,
        ParentAnalyticsService $analyticsService,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): void {
        if ($parent->students->isEmpty()) {
            return;
        }

        try {
            DB::transaction(function () use ($parent, $analyticsService, $periodStart, $periodEnd): void {
                User::query()
                    ->whereKey($parent->id)
                    ->lockForUpdate()
                    ->first();

                if ($this->digestAlreadySent($parent, $periodStart)) {
                    return;
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
                    return;
                }

                $digest = [
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'children' => $children,
                ];

                Mail::to($parent)->queue(new WeeklySummaryMailable($parent, $digest));

                try {
                    $parent->notifyNow(new WeeklySummaryWebPushNotification($digest));
                } catch (Throwable $exception) {
                    Log::warning('Weekly digest WebPush dispatch failed.', [
                        'parent_id' => $parent->id,
                        'message' => $exception->getMessage(),
                    ]);

                    $this->pruneExpiredPushSubscriptions($parent, $exception);
                }

                $rows = $parent->students->map(
                    static fn (Student $student): array => [
                        'parent_id' => $parent->id,
                        'student_id' => $student->id,
                        'report_type' => 'weekly_digest',
                        'start_date' => $periodStart->toDateString(),
                        'end_date' => $periodEnd->toDateString(),
                        'file_path' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                )->all();

                ParentReportLog::query()->insert($rows);
            });
        } catch (Throwable $exception) {
            Log::warning('Weekly digest dispatch failed.', [
                'parent_id' => $parent->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function digestAlreadySent(User $parent, Carbon $periodStart): bool
    {
        return ParentReportLog::query()
            ->where('parent_id', $parent->id)
            ->where('report_type', 'weekly_digest')
            ->whereDate('start_date', $periodStart->toDateString())
            ->exists();
    }

    private function pruneExpiredPushSubscriptions(User $parent, Throwable $exception): void
    {
        $message = strtolower($exception->getMessage());

        if (
            ! str_contains($message, '410')
            && ! str_contains($message, 'gone')
            && ! str_contains($message, 'expired')
            && ! str_contains($message, '404')
        ) {
            return;
        }

        $parent->loadMissing('pushSubscriptions');

        foreach ($parent->pushSubscriptions as $subscription) {
            $parent->deletePushSubscription($subscription->endpoint);
        }
    }
}
