<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InteractiveLessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject_code')
                    ->label('المادة')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('عنوان الدرس')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lesson_key')
                    ->label('المفتاح')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('station_count')
                    ->label('المحطات')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'منشور',
                        'draft' => 'مسودة',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
