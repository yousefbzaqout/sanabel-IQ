<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Analytics\MasteryAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MasteryAnalyticsController extends Controller
{
    public function show(Student $student, MasteryAnalyticsService $analytics): View
    {
        $this->authorize('viewMastery', $student);

        return view('parent.mastery-analytics.show', [
            'student' => $student,
            'analytics' => $analytics->forStudent($student),
        ]);
    }

    public function data(Student $student, MasteryAnalyticsService $analytics): JsonResponse
    {
        $this->authorize('viewMastery', $student);

        return response()->json($analytics->forStudent($student));
    }
}
