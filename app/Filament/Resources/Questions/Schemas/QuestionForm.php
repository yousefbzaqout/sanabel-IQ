<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Schemas;

use App\Enums\QuestionType;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('learning_material_id')
                    ->label(__('Learning Material'))
                    ->relationship('learningMaterial', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label(__('Question Type'))
                    ->options([
                        QuestionType::Mcq->value => __('Multiple Choice'),
                        QuestionType::TrueFalse->value => __('True / False'),
                        QuestionType::FillBlank->value => __('Fill in the Blank'),
                    ])
                    ->required()
                    ->native(false),
                Textarea::make('prompt')
                    ->label(__('Prompt'))
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Textarea::make('explanation')
                    ->label(__('Explanation'))
                    ->maxLength(5000)
                    ->columnSpanFull(),
                TextInput::make('points')
                    ->label(__('Points'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(10),
                Repeater::make('options')
                    ->label(__('Options'))
                    ->schema([
                        TextInput::make('option_text')
                            ->label(__('Option Text'))
                            ->required()
                            ->maxLength(1000),
                        Checkbox::make('is_correct')
                            ->label(__('Correct Answer')),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(2)
                    ->columnSpanFull(),
            ]);
    }
}
