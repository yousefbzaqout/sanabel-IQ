<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class ReportSummaryDTO
{
    /**
     * @param  Collection<int, array<string, mixed>>  $quizHistory
     * @param  list<array{name: string, code: string, unlocked_at: string|null}>  $badgesUnlocked
     * @param  list<array{title: string, status: string, target_xp: int}>  $parentGoals
     */
    public function __construct(
        public Student $student,
        public string $reportType,
        public Carbon $startDate,
        public Carbon $endDate,
        public int $xpEarnedInPeriod,
        public int $quizAccuracyPercent,
        public int $completedGoalsCount,
        public int $currentStreak,
        public int $activitiesCompleted,
        public int $quizzesCompleted,
        public Collection $quizHistory,
        public array $badgesUnlocked,
        public array $parentGoals,
    ) {}
}
