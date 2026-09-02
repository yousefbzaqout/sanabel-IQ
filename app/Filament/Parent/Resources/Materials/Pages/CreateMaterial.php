<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials\Pages;

use App\Enums\MaterialStatus;
use App\Filament\Parent\Resources\Materials\MaterialResource;
use App\Jobs\ProcessPDFMaterialJob;
use App\Support\ActiveChildResolver;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMaterial extends CreateRecord
{
    protected static string $resource = MaterialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());

        abort_if($student === null, 403);

        $uploadedPath = $data['uploaded_file'] ?? null;
        unset($data['uploaded_file']);

        $data['user_id'] = auth()->id();
        $data['student_id'] = $student->id;
        $data['status'] = MaterialStatus::Pending;

        if (is_string($uploadedPath) && $uploadedPath !== '') {
            $hashedFilename = hash('sha256', $uploadedPath.microtime(true)).'.pdf';
            $storedPath = Storage::disk('materials')->putFileAs('', Storage::disk('local')->path($uploadedPath), $hashedFilename);
            $data['file_path'] = is_string($storedPath) ? $storedPath : $hashedFilename;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessPDFMaterialJob::dispatch($this->record);
    }
}
