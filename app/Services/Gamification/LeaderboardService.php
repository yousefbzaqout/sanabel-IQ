<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    private const CACHE_TTL_SECONDS = 300;

    private const LIMIT = 10;

    /**
     * @return Collection<int, Student>
     */
    public function forGradeLevel(int $gradeLevel): Collection
    {
        return Cache::remember(
            $this->cacheKey($gradeLevel),
            self::CACHE_TTL_SECONDS,
            fn (): Collection => Student::query()
                ->where('grade_level', $gradeLevel)
                ->orderByDesc('total_xp')
                ->orderBy('id')
                ->limit(self::LIMIT)
                ->get(),
        );
    }

    public function rankForStudent(Student $student): int
    {
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
        Cache::forget($this->cacheKey($gradeLevel));
    }

    private function cacheKey(int $gradeLevel): string
    {
        return "leaderboard.grade.{$gradeLevel}";
    }
}
