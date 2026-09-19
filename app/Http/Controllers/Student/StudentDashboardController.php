<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Services\Gamification\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboardService): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');
        /** @var Student $student */
        $student = $request->user()->findAccessibleStudentOrFail($activeStudentId);

        $materials = LearningMaterial::query()
            ->published()
            ->with('interactiveLesson')
            ->whereHas('subject', fn ($query) => $query->where('grade_level', $student->grade_level))
            ->orderBy('title')
            ->limit(6)
            ->get();

        return view('student.dashboard.index', [
            'student' => $student,
            'materials' => $materials,
            'weeklyRank' => $leaderboardService->rankForStudent($student, 'weekly'),
            'badges' => $student->badges()->orderByPivot('unlocked_at', 'desc')->limit(6)->get(),
            'streakDays' => (int) ($student->streak?->current_streak ?? 0),
        ]);
    }
}
