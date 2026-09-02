<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('الابن/الابنة'),
                TextColumn::make('subject.name')
                    ->label('المادة')
                    ->placeholder('عام'),
                TextColumn::make('target_activity_count')
                    ->label('الأنشطة'),
                TextColumn::make('target_xp')
                    ->label('XP'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
