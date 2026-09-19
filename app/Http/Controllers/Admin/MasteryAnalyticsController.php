<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\MasteryAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasteryAnalyticsController extends Controller
{
    public function show(Request $request, MasteryAnalyticsService $analytics): View
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        $studentId = $request->integer('student_id') ?: null;

        return view('admin.mastery-analytics.show', [
            'analytics' => $analytics->forClassroom($studentId),
        ]);
    }

    public function data(Request $request, MasteryAnalyticsService $analytics): JsonResponse
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        $studentId = $request->integer('student_id') ?: null;

        return response()->json($analytics->forClassroom($studentId));
    }
}
