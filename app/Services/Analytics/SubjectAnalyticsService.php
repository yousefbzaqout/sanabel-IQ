<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\ActivityAttempt;
use App\Models\Student;
use Illuminate\Support\Collection;

class SubjectAnalyticsService
{
    private const WEAKNESS_THRESHOLD = 60;

    /**
     * @return array{
     *     total_questions_attempted: int,
     *     overall_accuracy_percent: int,
     *     subject_breakdown: list<array{
     *         subject: string,
     *         material_id: int|null,
     *         attempts_count: int,
     *         total_questions: int,
     *         correct_answers: int,
     *         accuracy_percent: int
     *     }>
     * }
     */
    public function analyze(Student $student): array
    {
        $attempts = ActivityAttempt::query()
            ->with(['activity.parentMaterial'])
            ->where('student_id', $student->id)
            ->get();

        $totalQuestions = $attempts->sum('total_questions');
        $totalCorrect = $attempts->sum('score');

        $subjectBreakdown = $attempts
            ->groupBy(function (ActivityAttempt $attempt): string {
                $material = $attempt->activity?->parentMaterial;

                if ($material === null) {
                    return 'uncategorized:'.$attempt->activity_id;
                }

                return 'material:'.$material->id;
            })
            ->map(function (Collection $groupedAttempts): array {
                /** @var ActivityAttempt $firstAttempt */
                $firstAttempt = $groupedAttempts->first();
                $material = $firstAttempt->activity?->parentMaterial;
                $questions = $groupedAttempts->sum('total_questions');
                $correct = $groupedAttempts->sum('score');

                return [
                    'subject' => $material?->title ?? __('Uncategorized Activity'),
                    'material_id' => $material?->id,
                    'attempts_count' => $groupedAttempts->count(),
                    'total_questions' => $questions,
                    'correct_answers' => $correct,
                    'accuracy_percent' => $this->accuracyPercent($correct, $questions),
                ];
            })
            ->sortByDesc('accuracy_percent')
            ->values()
            ->all();

        return [
            'total_questions_attempted' => $totalQuestions,
            'overall_accuracy_percent' => $this->accuracyPercent($totalCorrect, $totalQuestions),
            'subject_breakdown' => $subjectBreakdown,
        ];
    }

    /**
     * @return Collection<int, array{
     *     subject: string,
     *     material_id: int|null,
     *     attempts_count: int,
     *     total_questions: int,
     *     correct_answers: int,
     *     accuracy_percent: int
     * }>
     */
    public function identifyWeaknesses(Student $student): Collection
    {
        $analysis = $this->analyze($student);

        return collect($analysis['subject_breakdown'])
            ->filter(fn (array $subject): bool => $this->isWeakAccuracy(
                $subject['correct_answers'],
                $subject['total_questions'],
            ))
            ->values();
    }

    public function isWeakAccuracy(int $correct, int $total): bool
    {
        if ($total === 0) {
            return false;
        }

        return (($correct / $total) * 100) < self::WEAKNESS_THRESHOLD;
    }

    private function accuracyPercent(int $correct, int $total): int
    {
        if ($total === 0) {
            return 0;
        }

        return (int) round(($correct / $total) * 100);
    }
}
