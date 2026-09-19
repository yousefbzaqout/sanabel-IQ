<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Pages;

use App\Filament\Resources\InteractiveLessons\InteractiveLessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveLessons extends ListRecords
{
    protected static string $resource = InteractiveLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
