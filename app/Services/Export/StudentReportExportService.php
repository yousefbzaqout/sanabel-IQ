<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Student;
use App\Services\Analytics\SubjectAnalyticsService;
use App\Services\Goals\ParentGoalEvaluatorService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportExportService
{
    public function __construct(
        private readonly SubjectAnalyticsService $subjectAnalytics,
        private readonly ParentGoalEvaluatorService $goalEvaluator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(Student $student): array
    {
        $analysis = $this->subjectAnalytics->analyze($student);
        $attempts = $student->activityAttempts()
            ->with(['activity.parentMaterial'])
            ->latest('completed_at')
            ->get();

        $badges = $student->badges()
            ->orderByPivot('unlocked_at', 'desc')
            ->get()
            ->map(static fn ($badge): array => [
                'name' => $badge->name,
                'unlocked_at' => $badge->pivot?->unlocked_at?->toDateTimeString(),
            ])
            ->all();

        $activeGoals = $student->parentLearningGoals()
            ->where('status', 'pending')
            ->with('subject')
            ->get()
            ->map(function ($goal): array {
                $progress = $this->goalEvaluator->progressForGoal($goal->student, $goal);

                return [
                    'subject' => $goal->subject?->name ?? __('General'),
                    'target_activity_count' => $goal->target_activity_count,
                    'target_xp' => $goal->target_xp,
                    'activities_completed' => $progress['activities_completed'],
                    'xp_earned' => $progress['xp_earned'],
                ];
            })
            ->all();

        return [
            'student' => $student,
            'analysis' => $analysis,
            'attempts' => $attempts,
            'badges' => $badges,
            'activeGoals' => $activeGoals,
        ];
    }

    public function toCsvStream(Student $student): StreamedResponse
    {
        $report = $this->buildReportData($student);
        $filename = 'student-progress-'.$student->id.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Field', 'Value']);
            fputcsv($handle, ['Student Name', $report['student']->name]);
            fputcsv($handle, ['Grade Level', (string) $report['student']->grade_level]);
            fputcsv($handle, ['Total XP', (string) $report['student']->total_xp]);
            fputcsv($handle, ['Overall Accuracy %', (string) $report['analysis']['overall_accuracy_percent']]);
            fputcsv($handle, ['Questions Attempted', (string) $report['analysis']['total_questions_attempted']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Activity Attempts']);
            fputcsv($handle, ['Completed At', 'Score', 'Total Questions', 'XP Earned', 'Accuracy %']);

            foreach ($report['attempts'] as $attempt) {
                $accuracy = $attempt->total_questions > 0
                    ? (int) round(($attempt->score / $attempt->total_questions) * 100)
                    : 0;

                fputcsv($handle, [
                    $attempt->completed_at?->toDateTimeString(),
                    (string) $attempt->score,
                    (string) $attempt->total_questions,
                    (string) $attempt->xp_earned,
                    (string) $accuracy,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Badges']);
            fputcsv($handle, ['Badge Name', 'Unlocked At']);

            foreach ($report['badges'] as $badge) {
                fputcsv($handle, [$badge['name'], $badge['unlocked_at'] ?? '']);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
