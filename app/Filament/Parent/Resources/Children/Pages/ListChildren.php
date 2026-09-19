<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children\Pages;

use App\Filament\Parent\Resources\Children\ChildResource;
use App\Filament\Parent\Widgets\StitchChildrenOverviewWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ListChildren extends ListRecords
{
    protected static string $resource = ChildResource::class;

    protected static ?string $title = 'إدارة الأبناء';

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            StitchChildrenOverviewWidget::class,
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

    public function content(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
