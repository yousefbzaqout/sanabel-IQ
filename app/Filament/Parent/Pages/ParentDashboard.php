<?php

declare(strict_types=1);

namespace App\Filament\Parent\Pages;

use App\Filament\Parent\Widgets\StitchParentDashboardWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class ParentDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم العامة';

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            StitchParentDashboardWidget::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return 1;
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
