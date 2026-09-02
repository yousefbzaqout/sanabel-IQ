<?php

declare(strict_types=1);

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\LearningMaterial;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningMaterial extends CreateRecord
{
    protected static string $resource = LearningMaterialResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $subjectId = (int) $data['subject_id'];
        $maxOrder = LearningMaterial::query()
            ->where('subject_id', $subjectId)
            ->max('order_column');

        $data['order_column'] = $maxOrder === null ? 1 : ((int) $maxOrder) + 1;

        return $data;
    }
}
