<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Enums\ParentGoalStatus;
use App\Models\ActivityAttempt;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use App\Models\User;
use App\Notifications\GoalAchievedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ParentGoalEvaluatorService
{
    public function evaluate(Student $student): void
    {
        ParentLearningGoal::query()
            ->where('student_id', $student->id)
            ->where('status', ParentGoalStatus::Pending)
            ->orderBy('id')
            ->get()
            ->each(fn (ParentLearningGoal $goal): mixed => $this->evaluateGoal($student, $goal));
    }

    public function evaluateGoal(Student $student, ParentLearningGoal $goal): void
    {
        DB::transaction(function () use ($student, $goal): void {
            $lockedGoal = ParentLearningGoal::query()
                ->whereKey($goal->id)
                ->lockForUpdate()
                ->first();

            if ($lockedGoal === null || $lockedGoal->status !== ParentGoalStatus::Pending) {
                return;
            }

            $today = now()->startOfDay();

            if ($today->gt($lockedGoal->end_date)) {
                $lockedGoal->update(['status' => ParentGoalStatus::Expired]);

                return;
            }

            if ($today->lt($lockedGoal->start_date)) {
                return;
            }

            $progress = $this->progressForGoal($student, $lockedGoal);

            if ($progress['activities_completed'] < $lockedGoal->target_activity_count
                || $progress['xp_earned'] < $lockedGoal->target_xp) {
                return;
            }

            $lockedGoal->update(['status' => ParentGoalStatus::Achieved]);

            $parent = $lockedGoal->parent;

            if ($parent instanceof User) {
                $parent->notify(new GoalAchievedNotification($lockedGoal->fresh(['student', 'subject'])));
            }
        });
    }

    /**
     * @return array{activities_completed: int, xp_earned: int}
     */
    public function progressForGoal(Student $student, ParentLearningGoal $goal): array
    {
        $start = Carbon::parse($goal->start_date)->startOfDay();
        $end = Carbon::parse($goal->end_date)->endOfDay();

        $query = ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->whereBetween('completed_at', [$start, $end]);

        if ($goal->subject_id !== null) {
            $query->whereHas('activity.parentMaterial', function ($materialQuery) use ($goal): void {
                $materialQuery->where('subject_id', $goal->subject_id);
            });
        }

        $attempts = $query->get();

        return [
            'activities_completed' => $attempts->count(),
            'xp_earned' => (int) $attempts->sum('xp_earned'),
        ];
    }
}
