<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\Student;
use App\Models\StudentStreak;
use Illuminate\Support\Carbon;

class StreakTrackerService
{
    public function recordActivity(Student $student, ?Carbon $activityDate = null): StudentStreak
    {
        $activityDate ??= now();
        $today = $activityDate->copy()->startOfDay();

        $streak = StudentStreak::query()->firstOrCreate(
            ['student_id' => $student->id],
            [
                'current_streak' => 0,
                'max_streak' => 0,
                'last_activity_date' => null,
            ],
        );

        if ($streak->last_activity_date === null) {
            $streak->fill([
                'current_streak' => 1,
                'max_streak' => max(1, $streak->max_streak),
                'last_activity_date' => $today,
            ])->save();

            return $streak->fresh();
        }

        $lastActivityDate = $streak->last_activity_date->copy()->startOfDay();

        if ($lastActivityDate->equalTo($today)) {
            return $streak;
        }

        if ($lastActivityDate->copy()->addDay()->equalTo($today)) {
            $currentStreak = $streak->current_streak + 1;
        } else {
            $currentStreak = 1;
        }

        $streak->fill([
            'current_streak' => $currentStreak,
            'max_streak' => max($streak->max_streak, $currentStreak),
            'last_activity_date' => $today,
        ])->save();

        return $streak->fresh();
    }
}
