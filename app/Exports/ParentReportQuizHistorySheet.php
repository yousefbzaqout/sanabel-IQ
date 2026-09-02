<?php

declare(strict_types=1);

namespace App\Exports;

use App\DTOs\ReportSummaryDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ParentReportQuizHistorySheet implements Export, FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly ReportSummaryDTO $summary) {}

    public function collection(): Collection
    {
        return $this->summary->quizHistory->map(static fn (array $attempt): array => [
            $attempt['title'],
            $attempt['completed_at'],
            $attempt['correct_answers'],
            $attempt['total_questions'],
            $attempt['score_percentage'],
            $attempt['xp_earned'],
        ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            __('Quiz'),
            __('Completed At'),
            __('Correct'),
            __('Total'),
            __('Score %'),
            __('XP'),
        ];
    }

    public function title(): string
    {
        return __('Quiz History');
    }
}
