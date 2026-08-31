<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\ActivityAttempt;
use App\Models\Student;
use App\Models\User;
use App\Services\Analytics\SubjectAnalyticsService;
use Illuminate\Support\Carbon;

class WeeklyParentSummaryService
{
    public function __construct(private readonly SubjectAnalyticsService $subjectAnalytics) {}

    public function parentHasWeeklyActivity(User $parent, ?Carbon $since = null): bool
    {
        $since ??= now()->subDays(7);

        $studentIds = $parent->students()->pluck('students.id');

        if ($studentIds->isEmpty()) {
            return false;
        }

        return ActivityAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->where('completed_at', '>=', $since)
            ->exists();
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     children: list<array{
     *         student_id: int,
     *         name: string,
     *         grade_level: int
     *     }>
     * }
     */
    public function buildEncouragementForParent(User $parent, ?Carbon $since = null): array
    {
        $since ??= now()->subDays(7);

        $children = $parent->students()
            ->orderBy('id')
            ->get()
            ->map(static fn (Student $student): array => [
                'student_id' => $student->id,
                'name' => $student->name,
                'grade_level' => $student->grade_level,
            ])
            ->values()
            ->all();

        return [
            'period_start' => $since->toDateString(),
            'period_end' => now()->toDateString(),
            'children' => $children,
        ];
    }

    /**
     * @return array{
     *     period_start: string,
     *     period_end: string,
     *     children: list<array{
     *         student_id: int,
     *         name: string,
     *         grade_level: int,
     *         activities_completed: int,
     *         xp_earned: int,
     *         badges_unlocked: list<array{name: string, slug: string}>,
     *         weak_topics: list<array{subject: string, accuracy_percent: int}>
     *     }>
     * }
     */
    public function buildForParent(User $parent, ?Carbon $since = null): array
    {
        $since ??= now()->subDays(7);

        $children = $parent->students()
            ->orderBy('id')
            ->get()
            ->map(fn (Student $student): array => $this->buildForStudent($student, $since))
            ->values()
            ->all();

        return [
            'period_start' => $since->toDateString(),
            'period_end' => now()->toDateString(),
            'children' => $children,
        ];
    }

    /**
     * @return array{
     *     student_id: int,
     *     name: string,
     *     grade_level: int,
     *     activities_completed: int,
     *     xp_earned: int,
     *     badges_unlocked: list<array{name: string, slug: string}>,
     *     weak_topics: list<array{subject: string, accuracy_percent: int}>
     * }
     */
    public function buildForStudent(Student $student, ?Carbon $since = null): array
    {
        $since ??= now()->subDays(7);

        $attempts = $student->activityAttempts()
            ->where('completed_at', '>=', $since)
            ->get();

        $badgesUnlocked = $student->badges()
            ->wherePivot('unlocked_at', '>=', $since)
            ->get()
            ->map(static fn ($badge): array => [
                'name' => $badge->name,
                'slug' => $badge->slug,
            ])
            ->values()
            ->all();

        $weakTopics = $this->subjectAnalytics
            ->identifyWeaknesses($student)
            ->map(static fn (array $topic): array => [
                'subject' => $topic['subject'],
                'accuracy_percent' => $topic['accuracy_percent'],
            ])
            ->values()
            ->all();

        return [
            'student_id' => $student->id,
            'name' => $student->name,
            'grade_level' => $student->grade_level,
            'activities_completed' => $attempts->count(),
            'xp_earned' => (int) $attempts->sum('xp_earned'),
            'badges_unlocked' => $badgesUnlocked,
            'weak_topics' => $weakTopics,
        ];
    }
}
