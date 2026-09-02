<?php

declare(strict_types=1);

namespace App\Filament\Resources\LearningMaterials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LearningMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label(__('Subject'))
                    ->relationship('subject', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(2000)
                    ->columnSpanFull(),
                TextInput::make('xp_reward')
                    ->label(__('XP Reward'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(50),
                Toggle::make('is_published')
                    ->label(__('Published'))
                    ->default(false),
            ]);
    }
}
