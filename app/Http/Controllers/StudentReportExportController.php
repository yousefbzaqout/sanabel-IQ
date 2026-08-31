<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Export\StudentReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportExportController extends Controller
{
    public function export(Request $request, Student $student, StudentReportExportService $exportService): StreamedResponse|View
    {
        $this->authorize('export', $student);

        $format = (string) $request->query('format', 'csv');

        if ($format === 'pdf') {
            $report = $exportService->buildReportData($student);

            return view('exports.student-report-pdf', $report);
        }

        return $exportService->toCsvStream($student);
    }
}
