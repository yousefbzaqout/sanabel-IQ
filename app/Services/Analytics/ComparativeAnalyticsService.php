<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Student;
use App\Models\User;
use App\Services\Gamification\StudentGamification;
use App\Services\Goals\ParentGoalEvaluatorService;

class ComparativeAnalyticsService
{
    public function __construct(
        private readonly SubjectAnalyticsService $subjectAnalytics,
        private readonly ParentGoalEvaluatorService $goalEvaluator,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function compareChildren(User $parent): array
    {
        return $parent->students()
            ->orderBy('id')
            ->get()
            ->map(function (Student $student): array {
                $analysis = $this->subjectAnalytics->analyze($student);

                $activeGoals = $student->parentLearningGoals()
                    ->where('status', 'pending')
                    ->with('subject')
                    ->get()
                    ->map(function ($goal) use ($student): array {
                        $progress = $this->goalEvaluator->progressForGoal($student, $goal);

                        return [
                            'goal' => $goal,
                            'activities_completed' => $progress['activities_completed'],
                            'xp_earned' => $progress['xp_earned'],
                            'activity_progress_percent' => $goal->target_activity_count > 0
                                ? min(100, (int) round(($progress['activities_completed'] / $goal->target_activity_count) * 100))
                                : 0,
                            'xp_progress_percent' => $goal->target_xp > 0
                                ? min(100, (int) round(($progress['xp_earned'] / $goal->target_xp) * 100))
                                : 100,
                        ];
                    })
                    ->all();

                return [
                    'student' => $student,
                    'total_xp' => $student->total_xp,
                    'level' => StudentGamification::levelForXp($student->total_xp),
                    'overall_accuracy_percent' => $analysis['overall_accuracy_percent'],
                    'total_questions_attempted' => $analysis['total_questions_attempted'],
                    'subject_breakdown' => $analysis['subject_breakdown'],
                    'weak_topics' => $this->subjectAnalytics->identifyWeaknesses($student)->values()->all(),
                    'active_goals' => $activeGoals,
                ];
            })
            ->all();
    }
}
