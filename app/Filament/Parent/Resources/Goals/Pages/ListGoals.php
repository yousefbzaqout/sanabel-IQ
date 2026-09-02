<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals\Pages;

use App\Filament\Parent\Resources\Goals\GoalResource;
use Filament\Resources\Pages\ListRecords;

class ListGoals extends ListRecords
{
    protected static string $resource = GoalResource::class;
}
