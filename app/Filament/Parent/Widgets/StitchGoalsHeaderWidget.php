<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Enums\ParentGoalStatus;
use App\Support\ActiveChildResolver;
use Filament\Widgets\Widget;

class StitchGoalsHeaderWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.parent.widgets.stitch-goals-header';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());
        $goals = $student
            ? $student->parentLearningGoals()->latest()->get()
            : collect();

        $completed = $goals->filter(
            fn ($goal): bool => $goal->status === ParentGoalStatus::Achieved
        )->count();
        $total = $goals->count();
        $pct = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'student' => $student,
            'completed' => $completed,
            'total' => $total,
            'pct' => $pct,
        ];
    }
}
