<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children\Schemas;

use App\Support\StudentNameNormalizer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255)
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : StudentNameNormalizer::normalize($state))
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : StudentNameNormalizer::normalize($state)),
                Select::make('grade_level')
                    ->label('الصف')
                    ->options([
                        1 => 'الصف الأول',
                        2 => 'الصف الثاني',
                        3 => 'الصف الثالث',
                        4 => 'الصف الرابع',
                        5 => 'الصف الخامس',
                    ])
                    ->required()
                    ->native(false),
                Select::make('school_term')
                    ->label('الفصل الدراسي')
                    ->options([
                        1 => 'الفصل الأول',
                        2 => 'الفصل الثاني',
                    ])
                    ->required()
                    ->default(1)
                    ->native(false),
                TextInput::make('avatar_path')
                    ->label('مسار الصورة الرمزية')
                    ->maxLength(255),
            ]);
    }
}
