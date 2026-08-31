<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\Student;
use App\Services\Gamification\LeaderboardDisplayName;
use App\Services\Gamification\LeaderboardService;
use App\Services\Gamification\StudentGamification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboardService): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $recentAttempts = ActivityAttempt::query()
            ->with('activity')
            ->where('student_id', $student->id)
            ->latest('completed_at')
            ->limit(5)
            ->get();

        $accuracyPercent = $this->accuracyPercent($student);

        $earnedBadgeIds = $student->badges()->pluck('badges.id')->all();
        $badges = Badge::query()->orderBy('id')->get()->map(function (Badge $badge) use ($earnedBadgeIds, $student): array {
            $unlocked = in_array($badge->id, $earnedBadgeIds, true);
            $pivot = $unlocked
                ? $student->badges()->where('badges.id', $badge->id)->first()?->pivot
                : null;

            return [
                'badge' => $badge,
                'unlocked' => $unlocked,
                'unlocked_at' => $pivot?->unlocked_at,
                'progress_hint' => $this->badgeProgressHint($student, $badge, $unlocked),
            ];
        });

        return view('student.progress.index', [
            'student' => $student,
            'level' => StudentGamification::levelForXp($student->total_xp),
            'xpTowardsNextLevel' => StudentGamification::xpTowardsNextLevel($student->total_xp),
            'xpRequiredForNextLevel' => StudentGamification::xpRequiredForNextLevel($student->total_xp),
            'progressPercent' => StudentGamification::progressPercent($student->total_xp),
            'gradeRank' => $leaderboardService->rankForStudent($student),
            'badges' => $badges,
            'recentAttempts' => $recentAttempts,
            'accuracyPercent' => $accuracyPercent,
        ]);
    }

    public function leaderboard(Request $request, LeaderboardService $leaderboardService): View
    {
        $activeStudentId = (int) $request->session()->get('active_student_id');

        /** @var Student $student */
        $student = $request->user()->students()->findOrFail($activeStudentId);

        $entries = $leaderboardService->forGradeLevel($student->grade_level)
            ->values()
            ->map(function (Student $entry, int $index): array {
                return [
                    'rank' => $index + 1,
                    'student' => $entry,
                    'display_name' => LeaderboardDisplayName::format($entry->name),
                    'level' => StudentGamification::levelForXp($entry->total_xp),
                ];
            });

        return view('student.leaderboard.index', [
            'student' => $student,
            'entries' => $entries,
            'activeRank' => $leaderboardService->rankForStudent($student),
            'gradeLevel' => $student->grade_level,
        ]);
    }

    private function accuracyPercent(Student $student): int
    {
        $attempts = ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->get(['score', 'total_questions']);

        if ($attempts->isEmpty()) {
            return 0;
        }

        $totalQuestions = $attempts->sum('total_questions');
        $totalCorrect = $attempts->sum('score');

        if ($totalQuestions === 0) {
            return 0;
        }

        return (int) round(($totalCorrect / $totalQuestions) * 100);
    }

    private function badgeProgressHint(Student $student, Badge $badge, bool $unlocked): ?string
    {
        if ($unlocked) {
            return null;
        }

        return match ($badge->requirement_type) {
            'xp_threshold' => __(':current / :target XP', [
                'current' => $student->total_xp,
                'target' => $badge->requirement_value,
            ]),
            'activities_completed' => __(':current / :target activities completed', [
                'current' => $student->activityAttempts()->count(),
                'target' => $badge->requirement_value,
            ]),
            'perfect_scores' => __(':current / :target perfect scores', [
                'current' => $student->activityAttempts()->whereColumn('score', 'total_questions')->where('total_questions', '>', 0)->count(),
                'target' => $badge->requirement_value,
            ]),
            default => null,
        };
    }
}
