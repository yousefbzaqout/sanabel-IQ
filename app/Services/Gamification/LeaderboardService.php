<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    private const CACHE_TTL_SECONDS = 300;

    private const LIMIT = 10;

    /**
     * @return Collection<int, Student>
     */
    public function forGradeLevel(int $gradeLevel, string $period = 'alltime'): Collection
    {
        return Cache::remember(
            $this->cacheKey($gradeLevel, $period),
            self::CACHE_TTL_SECONDS,
            fn (): Collection => $period === 'weekly'
                ? $this->weeklyLeaderboard($gradeLevel)
                : $this->allTimeLeaderboard($gradeLevel),
        );
    }

    public function rankForStudent(Student $student, string $period = 'alltime'): int
    {
        if ($period === 'weekly') {
            $weekStart = now()->startOfWeek();
            $studentWeeklyXp = $this->weeklyXpForStudent($student, $weekStart);

            return Student::query()
                ->where('grade_level', $student->grade_level)
                ->get()
                ->filter(function (Student $peer) use ($student, $studentWeeklyXp, $weekStart): bool {
                    $peerWeeklyXp = $this->weeklyXpForStudent($peer, $weekStart);

                    return $peerWeeklyXp > $studentWeeklyXp
                        || ($peerWeeklyXp === $studentWeeklyXp && $peer->id < $student->id);
                })
                ->count() + 1;
        }

        return Student::query()
            ->where('grade_level', $student->grade_level)
            ->where(function ($query) use ($student): void {
                $query->where('total_xp', '>', $student->total_xp)
                    ->orWhere(function ($query) use ($student): void {
                        $query->where('total_xp', $student->total_xp)
                            ->where('id', '<', $student->id);
                    });
            })
            ->count() + 1;
    }

    public function flushGradeLevel(int $gradeLevel): void
    {
        Cache::forget($this->cacheKey($gradeLevel, 'alltime'));
        Cache::forget($this->cacheKey($gradeLevel, 'weekly'));
    }

    /**
     * @return Collection<int, Student>
     */
    private function allTimeLeaderboard(int $gradeLevel): Collection
    {
        return Student::query()
            ->with('streak')
            ->where('grade_level', $gradeLevel)
            ->orderByDesc('total_xp')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Student>
     */
    private function weeklyLeaderboard(int $gradeLevel): Collection
    {
        $weekStart = now()->startOfWeek();

        return Student::query()
            ->with('streak')
            ->where('grade_level', $gradeLevel)
            ->get()
            ->map(function (Student $student) use ($weekStart): Student {
                $activityXp = ActivityAttempt::query()
                    ->where('student_id', $student->id)
                    ->where('completed_at', '>=', $weekStart)
                    ->sum('xp_earned');

                $quizXp = StudentQuizAttempt::query()
                    ->where('student_id', $student->id)
                    ->where('completed_at', '>=', $weekStart)
                    ->sum('xp_earned');

                $student->setAttribute('weekly_xp', (int) $activityXp + (int) $quizXp);

                return $student;
            })
            ->sort(function (Student $left, Student $right): int {
                $xpComparison = ((int) $right->getAttribute('weekly_xp')) <=> ((int) $left->getAttribute('weekly_xp'));

                return $xpComparison !== 0 ? $xpComparison : $left->id <=> $right->id;
            })
            ->values()
            ->take(self::LIMIT);
    }

    private function cacheKey(int $gradeLevel, string $period): string
    {
        return "leaderboard.grade.{$gradeLevel}.{$period}";
    }

    private function weeklyXpForStudent(Student $student, \Illuminate\Support\Carbon $weekStart): int
    {
        $activityXp = ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->where('completed_at', '>=', $weekStart)
            ->sum('xp_earned');

        $quizXp = StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->where('completed_at', '>=', $weekStart)
            ->sum('xp_earned');

        return (int) $activityXp + (int) $quizXp;
    }
}
