<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials\Pages;

use App\Filament\Parent\Resources\Materials\MaterialResource;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;
}
