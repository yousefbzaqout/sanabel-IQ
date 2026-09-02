<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals\Pages;

use App\Enums\ParentGoalStatus;
use App\Filament\Parent\Resources\Goals\GoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGoal extends CreateRecord
{
    protected static string $resource = GoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parent_id'] = auth()->id();
        $data['status'] = ParentGoalStatus::Pending;

        return $data;
    }
}
