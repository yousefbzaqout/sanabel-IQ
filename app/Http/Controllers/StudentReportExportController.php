<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Export\PdfReportGeneratorService;
use App\Services\Export\StudentReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportExportController extends Controller
{
    public function export(
        Request $request,
        Student $student,
        StudentReportExportService $exportService,
        PdfReportGeneratorService $pdfReportGenerator,
    ): StreamedResponse|Response {
        $this->authorize('export', $student);

        $format = (string) $request->query('format', 'csv');

        if ($format === 'pdf') {
            $report = $exportService->buildReportData($student);
            $filename = sprintf('student-progress-%s.pdf', $student->id);

            return $pdfReportGenerator->downloadStudentReport($report, $filename);
        }

        return $exportService->toCsvStream($student);
    }
}
