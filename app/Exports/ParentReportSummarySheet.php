<?php

declare(strict_types=1);

namespace App\Exports;

use App\DTOs\ReportSummaryDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ParentReportSummarySheet implements Export, FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly ReportSummaryDTO $summary) {}

    public function collection(): Collection
    {
        return collect([
            [
                $this->summary->student->name,
                $this->summary->student->grade_level,
                $this->summary->startDate->toDateString(),
                $this->summary->endDate->toDateString(),
                $this->summary->xpEarnedInPeriod,
                $this->summary->quizAccuracyPercent,
                $this->summary->completedGoalsCount,
                $this->summary->currentStreak,
                $this->summary->activitiesCompleted,
                $this->summary->quizzesCompleted,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('Student'),
            __('Grade'),
            __('Start Date'),
            __('End Date'),
            __('XP Earned'),
            __('Quiz Accuracy %'),
            __('Completed Goals'),
            __('Current Streak'),
            __('Activities Completed'),
            __('Quizzes Completed'),
        ];
    }

    public function title(): string
    {
        return __('Summary');
    }
}
