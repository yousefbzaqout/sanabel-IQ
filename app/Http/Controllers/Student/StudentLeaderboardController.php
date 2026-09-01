<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Gamification\LeaderboardDisplayName;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StudentGamification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentLeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboardService): View|JsonResponse
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $gradeLevel = (int) $request->integer('grade', $student->grade_level);
        $period = $request->string('period', 'alltime')->toString();

        if (! in_array($period, ['alltime', 'weekly'], true)) {
            $period = 'alltime';
        }

        $leaderboardStudents = $leaderboardService->forGradeLevel($gradeLevel, $period);

        $entries = $leaderboardStudents
            ->values()
            ->map(function (Student $entry, int $index) use ($period): array {
                return [
                    'rank' => $index + 1,
                    'student' => $entry,
                    'display_name' => LeaderboardDisplayName::format($entry->name),
                    'level' => StudentGamification::levelForXp($entry->total_xp),
                    'current_streak' => (int) ($entry->streak?->current_streak ?? 0),
                    'total_xp' => $period === 'weekly'
                        ? (int) $entry->getAttribute('weekly_xp')
                        : $entry->total_xp,
                ];
            });

        $activeRank = $leaderboardService->rankForStudent(
            $student->grade_level === $gradeLevel ? $student : $student,
            $period,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'grade_level' => $gradeLevel,
                'period' => $period,
                'active_rank' => $student->grade_level === $gradeLevel ? $activeRank : null,
                'entries' => $entries->map(fn (array $entry): array => [
                    'rank' => $entry['rank'],
                    'display_name' => $entry['display_name'],
                    'total_xp' => $entry['total_xp'],
                    'level' => $entry['level'],
                    'current_streak' => $entry['current_streak'],
                ]),
            ]);
        }

        return view('student.leaderboard.index', [
            'student' => $student,
            'entries' => $entries,
            'activeRank' => $activeRank,
            'gradeLevel' => $gradeLevel,
            'period' => $period,
        ]);
    }
}
