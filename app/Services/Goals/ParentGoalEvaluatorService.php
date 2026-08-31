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

class ParentGoalEvaluatorService
{
    public function evaluate(Student $student): void
    {
        $today = now()->startOfDay();

        ParentLearningGoal::query()
            ->where('student_id', $student->id)
            ->where('status', ParentGoalStatus::Pending)
            ->get()
            ->each(function (ParentLearningGoal $goal) use ($student, $today): void {
                if ($today->gt($goal->end_date)) {
                    $goal->update(['status' => ParentGoalStatus::Expired]);

                    return;
                }

                if ($today->lt($goal->start_date)) {
                    return;
                }

                $progress = $this->progressForGoal($student, $goal);

                if ($progress['activities_completed'] >= $goal->target_activity_count
                    && $progress['xp_earned'] >= $goal->target_xp) {
                    $goal->update(['status' => ParentGoalStatus::Achieved]);

                    $parent = $goal->parent;

                    if ($parent instanceof User) {
                        $parent->notify(new GoalAchievedNotification($goal->fresh(['student', 'subject'])));
                    }
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
