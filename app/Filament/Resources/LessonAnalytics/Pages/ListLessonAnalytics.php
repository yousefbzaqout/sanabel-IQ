<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonAnalytics\Pages;

use App\Filament\Resources\LessonAnalytics\LessonAnalyticsResource;
use Filament\Resources\Pages\ListRecords;

class ListLessonAnalytics extends ListRecords
{
    protected static string $resource = LessonAnalyticsResource::class;
}
