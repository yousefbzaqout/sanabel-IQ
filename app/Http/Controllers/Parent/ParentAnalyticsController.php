<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\DTOs\ReportSummaryDTO;
use App\Http\Controllers\Controller;
use App\Models\ParentReportLog;
use App\Models\Student;
use App\Services\Analytics\ParentAnalyticsService;
use App\Services\Export\ExcelReportExporterService;
use App\Services\Export\PdfReportGeneratorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ParentAnalyticsController extends Controller
{
    public function show(
        Request $request,
        Student $student,
        ParentAnalyticsService $analyticsService,
    ): View {
        $this->authorize('view', $student);

        $period = $this->resolvePeriod($request);
        $summary = $analyticsService->buildSummary($student, $period);

        return view('parent.analytics.show', [
            'student' => $student,
            'summary' => $summary,
            'period' => $period,
        ]);
    }

    public function exportPdf(
        Request $request,
        Student $student,
        ParentAnalyticsService $analyticsService,
        PdfReportGeneratorService $pdfReportGenerator,
    ): Response {
        $this->authorize('export', $student);

        $period = $this->resolvePeriod($request);
        $summary = $analyticsService->buildSummary($student, $period);

        $this->logExport($request, $summary);

        $filename = sprintf(
            'sanabel-parent-report-%s-%s.pdf',
            $student->id,
            $summary->reportType,
        );

        return $pdfReportGenerator->download($summary, $filename);
    }

    public function exportExcel(
        Request $request,
        Student $student,
        ParentAnalyticsService $analyticsService,
        ExcelReportExporterService $excelReportExporter,
    ): BinaryFileResponse {
        $this->authorize('export', $student);

        $period = $this->resolvePeriod($request);
        $summary = $analyticsService->buildSummary($student, $period);

        $this->logExport($request, $summary);

        $filename = sprintf(
            'sanabel-parent-report-%s-%s.xlsx',
            $student->id,
            $summary->reportType,
        );

        return $excelReportExporter->download($summary, $filename);
    }

    private function resolvePeriod(Request $request): string
    {
        $period = strtolower((string) $request->query('period', 'weekly'));

        return in_array($period, ['weekly', 'monthly'], true) ? $period : 'weekly';
    }

    private function logExport(Request $request, ReportSummaryDTO $summary): void
    {
        ParentReportLog::query()->create([
            'parent_id' => $request->user()?->id,
            'student_id' => $summary->student->id,
            'report_type' => $summary->reportType,
            'start_date' => $summary->startDate->toDateString(),
            'end_date' => $summary->endDate->toDateString(),
            'file_path' => null,
        ]);
    }
}
