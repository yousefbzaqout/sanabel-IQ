<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $gradeLevel = (int) $data['grade_level'];

        $data['slug'] = Str::slug((string) $data['code']).'-'.$gradeLevel;

        return $data;
    }
}
