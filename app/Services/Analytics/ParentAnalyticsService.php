<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\DTOs\ReportSummaryDTO;
use App\Enums\ParentGoalStatus;
use App\Models\ActivityAttempt;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use Illuminate\Support\Carbon;

class ParentAnalyticsService
{
    public function buildSummary(Student $student, string $period = 'weekly'): ReportSummaryDTO
    {
        [$startDate, $endDate, $reportType] = $this->resolvePeriod($period);

        return $this->buildSummaryForRange($student, $startDate, $endDate, $reportType);
    }

    public function buildRollingSummary(Student $student, int $days = 7): ReportSummaryDTO
    {
        $startDate = now()->copy()->subDays($days)->startOfDay();
        $endDate = now()->copy()->endOfDay();

        return $this->buildSummaryForRange($student, $startDate, $endDate, 'weekly_digest');
    }

    private function buildSummaryForRange(
        Student $student,
        Carbon $startDate,
        Carbon $endDate,
        string $reportType,
    ): ReportSummaryDTO {
        $activityAttempts = ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();

        $quizAttempts = StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with('learningMaterial:id,title')
            ->orderByDesc('completed_at')
            ->get();

        $activityXp = (int) $activityAttempts->sum('xp_earned');
        $quizXp = (int) $quizAttempts->sum('xp_earned');

        $totalQuizQuestions = (int) $quizAttempts->sum('total_questions');
        $totalQuizCorrect = (int) $quizAttempts->sum('correct_answers');
        $quizAccuracyPercent = $totalQuizQuestions > 0
            ? (int) round(($totalQuizCorrect / $totalQuizQuestions) * 100)
            : 0;

        $completedGoalsCount = ParentLearningGoal::query()
            ->where('student_id', $student->id)
            ->where('status', ParentGoalStatus::Achieved)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $student->loadMissing('streak');

        $badgesUnlocked = $student->badges()
            ->wherePivot('unlocked_at', '>=', $startDate)
            ->wherePivot('unlocked_at', '<=', $endDate)
            ->get()
            ->map(static fn ($badge): array => [
                'name' => $badge->name_ar,
                'code' => $badge->code,
                'unlocked_at' => $badge->pivot->unlocked_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        $parentGoals = ParentLearningGoal::query()
            ->where('student_id', $student->id)
            ->orderByDesc('end_date')
            ->limit(10)
            ->get()
            ->map(static fn (ParentLearningGoal $goal): array => [
                'title' => __('Goal :count activities / :xp XP', [
                    'count' => $goal->target_activity_count,
                    'xp' => $goal->target_xp,
                ]),
                'status' => $goal->status->value,
                'target_xp' => $goal->target_xp,
            ])
            ->values()
            ->all();

        $quizHistory = $quizAttempts->map(static fn (StudentQuizAttempt $attempt): array => [
            'title' => $attempt->learningMaterial?->title ?? __('Quiz'),
            'completed_at' => $attempt->completed_at?->toDateTimeString(),
            'score_percentage' => (float) $attempt->score_percentage,
            'correct_answers' => $attempt->correct_answers,
            'total_questions' => $attempt->total_questions,
            'xp_earned' => $attempt->xp_earned,
        ]);

        return new ReportSummaryDTO(
            student: $student,
            reportType: $reportType,
            startDate: $startDate,
            endDate: $endDate,
            xpEarnedInPeriod: $activityXp + $quizXp,
            quizAccuracyPercent: $quizAccuracyPercent,
            completedGoalsCount: $completedGoalsCount,
            currentStreak: (int) ($student->streak?->current_streak ?? 0),
            activitiesCompleted: $activityAttempts->count(),
            quizzesCompleted: $quizAttempts->count(),
            quizHistory: $quizHistory,
            badgesUnlocked: $badgesUnlocked,
            parentGoals: $parentGoals,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolvePeriod(string $period): array
    {
        $normalized = strtolower($period);

        if ($normalized === 'monthly') {
            return [
                now()->copy()->startOfMonth()->startOfDay(),
                now()->copy()->endOfDay(),
                'monthly',
            ];
        }

        return [
            now()->copy()->startOfWeek()->startOfDay(),
            now()->copy()->endOfDay(),
            'weekly',
        ];
    }
}
