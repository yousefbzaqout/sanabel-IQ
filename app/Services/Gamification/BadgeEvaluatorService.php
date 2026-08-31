<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\Student;
use Illuminate\Support\Collection;

class BadgeEvaluatorService
{
    /**
     * @return Collection<int, Badge>
     */
    public function evaluate(Student $student, ?ActivityAttempt $latestAttempt = null): Collection
    {
        $student->refresh();
        $newlyUnlocked = collect();

        /** @var Collection<int, Badge> $badges */
        $badges = Badge::query()->orderBy('id')->get();
        $earnedBadgeIds = $student->badges()->pluck('badges.id')->all();

        foreach ($badges as $badge) {
            if (in_array($badge->id, $earnedBadgeIds, true)) {
                continue;
            }

            if (! $this->qualifies($student, $badge, $latestAttempt)) {
                continue;
            }

            $student->badges()->attach($badge->id, ['unlocked_at' => now()]);
            $newlyUnlocked->push($badge);
            $earnedBadgeIds[] = $badge->id;
        }

        return $newlyUnlocked;
    }

    private function qualifies(Student $student, Badge $badge, ?ActivityAttempt $latestAttempt): bool
    {
        return match ($badge->requirement_type) {
            'xp_threshold' => $student->total_xp >= $badge->requirement_value,
            'activities_completed' => $this->completedActivitiesCount($student) >= $badge->requirement_value,
            'perfect_scores' => $this->perfectScoreCount($student) >= $badge->requirement_value,
            default => false,
        };
    }

    private function completedActivitiesCount(Student $student): int
    {
        return ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->count();
    }

    private function perfectScoreCount(Student $student): int
    {
        return ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->whereColumn('score', 'total_questions')
            ->where('total_questions', '>', 0)
            ->count();
    }
}
