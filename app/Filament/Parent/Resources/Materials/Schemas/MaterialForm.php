<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('العنوان')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('النوع')
                    ->options([
                        'exam' => 'امتحان',
                        'summary' => 'ملخص',
                        'worksheet' => 'ورقة عمل',
                    ])
                    ->required()
                    ->native(false),
                FileUpload::make('uploaded_file')
                    ->label('ملف PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->required()
                    ->maxSize(10240),
            ]);
    }
}
