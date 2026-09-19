<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('ولي الأمر')
                    ->searchable(),
                TextColumn::make('grade_level')
                    ->label('الصف')
                    ->formatStateUsing(fn (int $state): string => "الصف {$state}")
                    ->sortable(),
                TextColumn::make('school_term')
                    ->label('الفصل')
                    ->formatStateUsing(fn (int $state): string => "الفصل {$state}")
                    ->sortable(),
                TextColumn::make('total_xp')
                    ->label('النقاط (XP)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
