<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemoRequests\Pages;

use App\Filament\Resources\DemoRequests\Actions\ConvertDemoRequestToTenantAction;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDemoRequest extends EditRecord
{
    protected static string $resource = DemoRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ConvertDemoRequestToTenantAction::make(),
            ViewAction::make(),
        ];
    }
}
