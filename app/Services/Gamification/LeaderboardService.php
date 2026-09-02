<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaderboardService
{
    private const CACHE_TTL_SECONDS = 300;

    private const LOCK_SECONDS = 10;

    private const LIMIT = 10;

    /**
     * @return Collection<int, Student>
     */
    public function forGradeLevel(int $gradeLevel, string $period = 'alltime'): Collection
    {
        try {
            return $this->rememberWithLock($gradeLevel, $period);
        } catch (Throwable $exception) {
            Log::warning('Leaderboard cache unavailable; falling back to database.', [
                'grade_level' => $gradeLevel,
                'period' => $period,
                'message' => $exception->getMessage(),
            ]);

            return $period === 'weekly'
                ? $this->weeklyLeaderboard($gradeLevel)
                : $this->allTimeLeaderboard($gradeLevel);
        }
    }

    public function rankForStudent(Student $student, string $period = 'alltime'): int
    {
        if ($period === 'weekly') {
            return $this->weeklyRankForStudent($student);
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
        foreach (['alltime', 'weekly'] as $period) {
            try {
                Cache::forget($this->cacheKey($gradeLevel, $period));
            } catch (Throwable $exception) {
                Log::warning('Unable to flush leaderboard cache.', [
                    'grade_level' => $gradeLevel,
                    'period' => $period,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return Collection<int, Student>
     */
    private function rememberWithLock(int $gradeLevel, string $period): Collection
    {
        $key = $this->cacheKey($gradeLevel, $period);

        $cached = Cache::get($key);

        if ($cached instanceof Collection) {
            return $cached;
        }

        return Cache::lock($this->lockKey($gradeLevel, $period), self::LOCK_SECONDS)->block(5, function () use ($key, $gradeLevel, $period): Collection {
            $cached = Cache::get($key);

            if ($cached instanceof Collection) {
                return $cached;
            }

            $leaderboard = $period === 'weekly'
                ? $this->weeklyLeaderboard($gradeLevel)
                : $this->allTimeLeaderboard($gradeLevel);

            Cache::put($key, $leaderboard, self::CACHE_TTL_SECONDS);

            return $leaderboard;
        });
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

        $activityXpByStudent = ActivityAttempt::query()
            ->selectRaw('student_id, COALESCE(SUM(xp_earned), 0) as weekly_xp')
            ->where('completed_at', '>=', $weekStart)
            ->groupBy('student_id')
            ->pluck('weekly_xp', 'student_id');

        $quizXpByStudent = StudentQuizAttempt::query()
            ->selectRaw('student_id, COALESCE(SUM(xp_earned), 0) as weekly_xp')
            ->where('completed_at', '>=', $weekStart)
            ->groupBy('student_id')
            ->pluck('weekly_xp', 'student_id');

        return Student::query()
            ->with('streak')
            ->where('grade_level', $gradeLevel)
            ->get()
            ->map(function (Student $student) use ($activityXpByStudent, $quizXpByStudent): Student {
                $weeklyXp = (int) ($activityXpByStudent[$student->id] ?? 0)
                    + (int) ($quizXpByStudent[$student->id] ?? 0);

                $student->setAttribute('weekly_xp', $weeklyXp);

                return $student;
            })
            ->sort(function (Student $left, Student $right): int {
                $xpComparison = ((int) $right->getAttribute('weekly_xp')) <=> ((int) $left->getAttribute('weekly_xp'));

                return $xpComparison !== 0 ? $xpComparison : $left->id <=> $right->id;
            })
            ->values()
            ->take(self::LIMIT);
    }

    private function weeklyRankForStudent(Student $student): int
    {
        $weekStart = now()->startOfWeek()->toDateTimeString();

        $result = DB::selectOne(
            <<<'SQL'
                WITH weekly_xp AS (
                    SELECT student_id, SUM(xp_earned) AS xp
                    FROM (
                        SELECT student_id, xp_earned
                        FROM activity_attempts
                        WHERE completed_at >= ?
                        UNION ALL
                        SELECT student_id, xp_earned
                        FROM student_quiz_attempts
                        WHERE completed_at >= ?
                    ) AS attempts
                    GROUP BY student_id
                ),
                target AS (
                    SELECT COALESCE(xp, 0) AS xp
                    FROM weekly_xp
                    WHERE student_id = ?
                    UNION ALL
                    SELECT 0
                    LIMIT 1
                )
                SELECT COUNT(*) + 1 AS rank
                FROM students AS peers
                LEFT JOIN weekly_xp ON weekly_xp.student_id = peers.id
                CROSS JOIN target
                WHERE peers.grade_level = ?
                  AND (
                    COALESCE(weekly_xp.xp, 0) > target.xp
                    OR (
                        COALESCE(weekly_xp.xp, 0) = target.xp
                        AND peers.id < ?
                    )
                  )
            SQL,
            [
                $weekStart,
                $weekStart,
                $student->id,
                $student->grade_level,
                $student->id,
            ],
        );

        return (int) ($result->rank ?? 1);
    }

    private function cacheKey(int $gradeLevel, string $period): string
    {
        return "leaderboard.grade.{$gradeLevel}.{$period}";
    }

    private function lockKey(int $gradeLevel, string $period): string
    {
        return "leaderboard.lock.grade.{$gradeLevel}.{$period}";
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
