<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Pages;

use App\Filament\Resources\InteractiveLessons\InteractiveLessonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveLesson extends EditRecord
{
    protected static string $resource = InteractiveLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
