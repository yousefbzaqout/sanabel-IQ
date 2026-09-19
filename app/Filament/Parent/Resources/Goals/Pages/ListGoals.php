<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals\Pages;

use App\Filament\Parent\Resources\Goals\GoalResource;
use App\Filament\Parent\Widgets\StitchGoalsHeaderWidget;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListGoals extends ListRecords
{
    protected static string $resource = GoalResource::class;

    protected static ?string $title = 'أهداف وتحديات الأسبوع';

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            StitchGoalsHeaderWidget::class,
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<\Filament\Actions\Action|\Filament\Actions\ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
