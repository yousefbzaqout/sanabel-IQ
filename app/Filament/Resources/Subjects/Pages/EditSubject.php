<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $gradeLevel = (int) $data['grade_level'];

        $data['slug'] = Str::slug((string) $data['code']).'-'.$gradeLevel;

        return $data;
    }
}
