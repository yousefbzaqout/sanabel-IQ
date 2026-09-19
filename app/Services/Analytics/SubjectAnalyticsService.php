<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Support\Charts\SvgTrendPath;
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
     * Daily + cumulative accuracy series for mastery chart (data-driven SVG).
     *
     * @return array{
     *     has_data: bool,
     *     days: int,
     *     points: list<array{date: string, label: string, daily_accuracy: int|null, cumulative_accuracy: int|null, questions: int}>,
     *     paths: array{
     *         amber_stroke: string,
     *         amber_area: string,
     *         teal_stroke: string,
     *         teal_area: string
     *     }
     * }
     */
    public function accuracyTrend(Student $student, int $days = 7): array
    {
        $days = max(1, min(180, $days));
        $end = now()->copy()->endOfDay();
        $start = now()->copy()->subDays($days - 1)->startOfDay();

        $activityRows = ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->whereBetween('completed_at', [$start, $end])
            ->get(['score', 'total_questions', 'completed_at']);

        $quizRows = StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereBetween('completed_at', [$start, $end])
            ->get(['correct_answers', 'total_questions', 'completed_at']);

        /** @var array<string, array{correct: int, total: int}> $byDay */
        $byDay = [];

        foreach ($activityRows as $row) {
            if ($row->completed_at === null || $row->total_questions <= 0) {
                continue;
            }
            $key = $row->completed_at->toDateString();
            $byDay[$key] ??= ['correct' => 0, 'total' => 0];
            $byDay[$key]['correct'] += (int) $row->score;
            $byDay[$key]['total'] += (int) $row->total_questions;
        }

        foreach ($quizRows as $row) {
            if ($row->completed_at === null || $row->total_questions <= 0) {
                continue;
            }
            $key = $row->completed_at->toDateString();
            $byDay[$key] ??= ['correct' => 0, 'total' => 0];
            $byDay[$key]['correct'] += (int) $row->correct_answers;
            $byDay[$key]['total'] += (int) $row->total_questions;
        }

        $points = [];
        $dailySeries = [];
        $cumulativeSeries = [];
        $runningCorrect = 0;
        $runningTotal = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);
            $key = $day->toDateString();
            $bucket = $byDay[$key] ?? null;
            $dailyAccuracy = null;
            $questions = 0;

            if ($bucket !== null && $bucket['total'] > 0) {
                $questions = $bucket['total'];
                $runningCorrect += $bucket['correct'];
                $runningTotal += $bucket['total'];
                $dailyAccuracy = $this->accuracyPercent($bucket['correct'], $bucket['total']);
            }

            $cumulativeAccuracy = $runningTotal > 0
                ? $this->accuracyPercent($runningCorrect, $runningTotal)
                : null;

            $points[] = [
                'date' => $key,
                'label' => $day->translatedFormat('D'),
                'daily_accuracy' => $dailyAccuracy,
                'cumulative_accuracy' => $cumulativeAccuracy,
                'questions' => $questions,
            ];

            $dailySeries[] = $dailyAccuracy;
            $cumulativeSeries[] = $cumulativeAccuracy;
        }

        $hasData = $runningTotal > 0;
        $amber = SvgTrendPath::fromPercents($this->carryForward($cumulativeSeries));
        $teal = SvgTrendPath::fromPercents($this->carryForward($dailySeries));

        return [
            'has_data' => $hasData,
            'days' => $days,
            'points' => $points,
            'paths' => [
                'amber_stroke' => $hasData ? $amber['stroke'] : '',
                'amber_area' => $hasData ? $amber['area'] : '',
                'teal_stroke' => $hasData ? $teal['stroke'] : '',
                'teal_area' => $hasData ? $teal['area'] : '',
            ],
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

    /**
     * Fill leading nulls; keep gaps after first sample as carried value for continuous stroke.
     *
     * @param  list<int|null>  $series
     * @return list<int|null>
     */
    private function carryForward(array $series): array
    {
        $last = null;
        $out = [];

        foreach ($series as $value) {
            if ($value !== null) {
                $last = $value;
            }
            $out[] = $last;
        }

        return $out;
    }

    private function accuracyPercent(int $correct, int $total): int
    {
        if ($total === 0) {
            return 0;
        }

        return (int) round(($correct / $total) * 100);
    }
}
