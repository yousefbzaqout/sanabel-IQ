<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\DTOs\ReportSummaryDTO;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfReportGeneratorService
{
    public function download(ReportSummaryDTO $summary, string $filename): Response
    {
        $pdf = Pdf::loadView('pdf.parent-weekly-report', [
            'summary' => $summary,
            'student' => $summary->student,
        ])
            ->setPaper('a4')
            ->setOption('defaultFont', 'amiri');

        return $pdf->download($filename);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function downloadStudentReport(array $report, string $filename): Response
    {
        $pdf = Pdf::loadView('exports.student-report-pdf', $report)
            ->setPaper('a4')
            ->setOption('defaultFont', 'amiri');

        return $pdf->download($filename);
    }
}
