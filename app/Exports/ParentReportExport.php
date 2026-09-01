<?php

declare(strict_types=1);

namespace App\Exports;

use App\DTOs\ReportSummaryDTO;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ParentReportExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly ReportSummaryDTO $summary) {}

    /**
     * @return list<Export>
     */
    public function sheets(): array
    {
        return [
            new ParentReportSummarySheet($this->summary),
            new ParentReportQuizHistorySheet($this->summary),
        ];
    }
}
