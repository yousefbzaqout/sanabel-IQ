<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Models\LearningMaterial;
use App\Services\Analytics\SubjectAnalyticsService;
use App\Services\Gamification\LeaderboardService;
use App\Support\ActiveChildResolver;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class StitchParentDashboardWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.parent.widgets.stitch-parent-dashboard';

    public string $chartPeriod = '7';

    public function setChartPeriod(string $period): void
    {
        if (! in_array($period, ['7', '30', 'semester'], true)) {
            return;
        }

        $this->chartPeriod = $period;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());
        $siblings = auth()->user()?->students()->orderBy('name')->get() ?? collect();

        if ($student !== null) {
            $student->loadMissing(['tenant', 'streak']);
        }

        if ($student === null) {
            return [
                'student' => null,
                'siblings' => $siblings,
                'analysis' => null,
                'streakDays' => 0,
                'completedLessons' => 0,
                'totalLessons' => 0,
                'weeklyXp' => 0,
                'leaders' => collect(),
                'rank' => null,
                'skills' => collect(),
                'masteryTrend' => [
                    'has_data' => false,
                    'days' => 7,
                    'points' => [],
                    'paths' => [
                        'amber_stroke' => '',
                        'amber_area' => '',
                        'teal_stroke' => '',
                        'teal_area' => '',
                    ],
                ],
                'chartPeriod' => $this->chartPeriod,
            ];
        }

        $analytics = app(SubjectAnalyticsService::class);
        $analysis = $analytics->analyze($student);
        $masteryTrend = $analytics->accuracyTrend($student, $this->periodDays());
        $leaderboard = app(LeaderboardService::class);
        $completedLessons = (int) $student->quizAttempts()->distinct()->count('learning_material_id');
        $totalLessons = $this->publishedLessonsForGrade((int) $student->grade_level);
        if ($totalLessons < $completedLessons) {
            $totalLessons = $completedLessons;
        }

        return [
            'student' => $student,
            'siblings' => $siblings,
            'analysis' => $analysis,
            'streakDays' => (int) ($student->streak?->current_streak ?? 0),
            'completedLessons' => $completedLessons,
            'totalLessons' => $totalLessons,
            'weeklyXp' => (int) ($leaderboard->forGradeLevel($student->grade_level, 'weekly')
                ->firstWhere('id', $student->id)?->weekly_xp ?? 0),
            'leaders' => $leaderboard->forGradeLevel($student->grade_level, 'weekly')->take(4),
            'rank' => $leaderboard->rankForStudent($student, 'weekly'),
            'skills' => $this->skillBars($analysis['subject_breakdown'] ?? []),
            'masteryTrend' => $masteryTrend,
            'chartPeriod' => $this->chartPeriod,
        ];
    }

    private function periodDays(): int
    {
        return match ($this->chartPeriod) {
            '30' => 30,
            'semester' => 120,
            default => 7,
        };
    }

    private function publishedLessonsForGrade(int $gradeLevel): int
    {
        return (int) LearningMaterial::query()
            ->published()
            ->whereHas('subject', static fn ($query) => $query->where('grade_level', $gradeLevel))
            ->count();
    }

    /**
     * @param  list<array{subject: string, accuracy_percent: int, attempts_count?: int}>  $breakdown
     * @return Collection<int, array{label: string, percent: int, hint: string, tone: string}>
     */
    private function skillBars(array $breakdown): Collection
    {
        $tones = ['primary', 'secondary', 'tertiary', 'primary'];

        if ($breakdown === []) {
            return collect([
                ['label' => 'اللغة العربية', 'percent' => 0, 'hint' => 'بانتظار محاولات', 'tone' => 'primary'],
                ['label' => 'الرياضيات', 'percent' => 0, 'hint' => 'بانتظار محاولات', 'tone' => 'secondary'],
                ['label' => 'العلوم', 'percent' => 0, 'hint' => 'بانتظار محاولات', 'tone' => 'tertiary'],
                ['label' => 'النطق', 'percent' => 0, 'hint' => 'بانتظار محاولات', 'tone' => 'primary'],
            ]);
        }

        return collect($breakdown)
            ->take(4)
            ->values()
            ->map(function (array $row, int $index) use ($tones): array {
                return [
                    'label' => $row['subject'],
                    'percent' => (int) $row['accuracy_percent'],
                    'hint' => ($row['attempts_count'] ?? 0).' محاولات',
                    'tone' => $tones[$index % count($tones)],
                ];
            });
    }
}
