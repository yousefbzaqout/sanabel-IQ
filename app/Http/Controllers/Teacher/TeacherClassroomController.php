<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Analytics\MasteryAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherClassroomController extends Controller
{
    public function dashboard(Request $request, MasteryAnalyticsService $analytics): View
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() === true, 403);

        $students = Student::query()
            ->whereHas('user', function ($query) use ($teacher): void {
                $query->withoutGlobalScope('tenant')
                    ->where('tenant_id', $teacher->tenant_id);
            })
            ->orderBy('name')
            ->get();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'students' => $students,
            'classroom' => $analytics->forClassroom(),
        ]);
    }

    public function studentProgress(Request $request, Student $student, MasteryAnalyticsService $analytics): View
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher() === true, 403);
        $student->loadMissing('user');
        abort_unless(
            (int) $student->user?->tenant_id === (int) $teacher->tenant_id,
            403,
        );

        return view('teacher.student-progress', [
            'teacher' => $teacher,
            'student' => $student,
            'analytics' => $analytics->forStudent($student),
        ]);
    }
}
