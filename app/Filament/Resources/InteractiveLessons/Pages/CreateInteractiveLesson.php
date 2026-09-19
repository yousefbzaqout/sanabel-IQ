<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Pages;

use App\Filament\Resources\InteractiveLessons\InteractiveLessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInteractiveLesson extends CreateRecord
{
    protected static string $resource = InteractiveLessonResource::class;
}
