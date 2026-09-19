<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Services\Gamification\LeaderboardService;
use App\Support\ActiveChildResolver;
use Filament\Widgets\Widget;

class ParentLeaderboardWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'lg' => 3,
    ];

    protected string $view = 'filament.parent.widgets.parent-leaderboard';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());

        if ($student === null) {
            return [
                'rank' => null,
                'leaders' => collect(),
            ];
        }

        $leaderboard = app(LeaderboardService::class);

        return [
            'rank' => $leaderboard->rankForStudent($student, 'weekly'),
            'leaders' => $leaderboard->forGradeLevel($student->grade_level, 'weekly')->take(5),
        ];
    }
}
