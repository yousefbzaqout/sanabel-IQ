<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\DTOs\ReportSummaryDTO;
use App\Exports\ParentReportExport;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelReportExporterService
{
    public function download(ReportSummaryDTO $summary, string $filename): BinaryFileResponse
    {
        return ExcelFacade::download(
            new ParentReportExport($summary),
            $filename,
            Excel::XLSX,
        );
    }
}
