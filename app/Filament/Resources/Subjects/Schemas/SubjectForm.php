<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Schemas;

use App\Models\Subject;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->maxLength(50)
                    ->unique(
                        table: Subject::class,
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('grade_level', (int) $get('grade_level')),
                    ),
                Select::make('grade_level')
                    ->label(__('Grade'))
                    ->required()
                    ->options([
                        1 => __('Grade').' 1',
                        2 => __('Grade').' 2',
                        3 => __('Grade').' 3',
                        4 => __('Grade').' 4',
                        5 => __('Grade').' 5',
                    ])
                    ->native(false),
                TextInput::make('icon')
                    ->label(__('Icon'))
                    ->maxLength(100),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}
