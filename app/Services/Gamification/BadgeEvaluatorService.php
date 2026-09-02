<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Events\BadgeUnlockedBroadcastEvent;
use App\Enums\BadgeCriteriaType;
use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\StudentStreak;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BadgeEvaluatorService
{
    /**
     * @return Collection<int, Badge>
     */
    public function evaluate(Student $student): Collection
    {
        $newlyUnlocked = DB::transaction(function () use ($student): Collection {
            Student::query()
                ->whereKey($student->id)
                ->lockForUpdate()
                ->first();

            $student->refresh();
            $unlocked = collect();

            /** @var Collection<int, Badge> $badges */
            $badges = Badge::query()->orderBy('id')->get();
            $earnedBadgeIds = $student->badges()->pluck('badges.id')->all();

            foreach ($badges as $badge) {
                if (in_array($badge->id, $earnedBadgeIds, true)) {
                    continue;
                }

                if (! $this->qualifies($student, $badge)) {
                    continue;
                }

                $student->badges()->attach($badge->id, ['unlocked_at' => now()]);
                $unlocked->push($badge);
                $earnedBadgeIds[] = $badge->id;
            }

            return $unlocked;
        });

        $freshStudent = $student->fresh();

        if ($freshStudent !== null) {
            foreach ($newlyUnlocked as $badge) {
                BadgeUnlockedBroadcastEvent::dispatch(
                    parentId: (int) $freshStudent->user_id,
                    studentId: (int) $freshStudent->id,
                    childName: $freshStudent->name,
                    badgeCode: $badge->code,
                    badgeNameAr: $badge->name_ar,
                    badgeIcon: $badge->icon,
                );
            }
        }

        return $newlyUnlocked;
    }

    private function qualifies(Student $student, Badge $badge): bool
    {
        return match ($badge->criteria_type) {
            BadgeCriteriaType::XpThreshold => $student->total_xp >= $badge->criteria_value,
            BadgeCriteriaType::QuizCount => $this->completedQuizCount($student) >= $badge->criteria_value,
            BadgeCriteriaType::StreakDays => $this->currentStreakDays($student) >= $badge->criteria_value,
            BadgeCriteriaType::ActivitiesCompleted => $this->completedActivitiesCount($student) >= $badge->criteria_value,
            BadgeCriteriaType::PerfectScores => $this->perfectScoreCount($student) >= $badge->criteria_value,
        };
    }

    private function completedQuizCount(Student $student): int
    {
        return (int) StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->distinct()
            ->count('learning_material_id');
    }

    private function currentStreakDays(Student $student): int
    {
        return (int) ($student->streak?->current_streak ?? 0);
    }

    private function completedActivitiesCount(Student $student): int
    {
        return (int) ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->distinct()
            ->count('activity_id');
    }

    private function perfectScoreCount(Student $student): int
    {
        $activityPerfects = (int) ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->whereColumn('score', 'total_questions')
            ->where('total_questions', '>', 0)
            ->distinct()
            ->count('activity_id');

        $quizPerfects = (int) StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereColumn('correct_answers', 'total_questions')
            ->where('total_questions', '>', 0)
            ->distinct()
            ->count('learning_material_id');

        return $activityPerfects + $quizPerfects;
    }
}
