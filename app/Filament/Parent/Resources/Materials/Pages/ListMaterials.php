<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials\Pages;

use App\Filament\Parent\Resources\Materials\MaterialResource;
use App\Filament\Parent\Widgets\StitchMaterialsHeaderWidget;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected static ?string $title = 'المواد والدروس التعليمية';

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            StitchMaterialsHeaderWidget::class,
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
