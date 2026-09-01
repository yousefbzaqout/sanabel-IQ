<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentBadgeController extends Controller
{
    public function index(Request $request): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->with(['badges', 'streak'])->findOrFail($activeStudentId);

        $earnedBadgeIds = $student->badges->pluck('id')->all();

        $badges = Badge::query()
            ->orderBy('id')
            ->get()
            ->map(function (Badge $badge) use ($student, $earnedBadgeIds): array {
                $unlocked = in_array($badge->id, $earnedBadgeIds, true);
                $pivot = $unlocked
                    ? $student->badges->firstWhere('id', $badge->id)?->pivot
                    : null;

                return [
                    'badge' => $badge,
                    'unlocked' => $unlocked,
                    'unlocked_at' => $pivot?->unlocked_at,
                ];
            });

        return view('student.badges.index', [
            'student' => $student,
            'badges' => $badges,
            'currentStreak' => (int) ($student->streak?->current_streak ?? 0),
            'maxStreak' => (int) ($student->streak?->max_streak ?? 0),
        ]);
    }
}
