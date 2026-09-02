<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChildrenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grade_level')
                    ->label('الصف')
                    ->sortable(),
                TextColumn::make('total_xp')
                    ->label('XP')
                    ->sortable(),
                TextColumn::make('school_term')
                    ->label('الفصل')
                    ->formatStateUsing(fn (int $state): string => $state === 2 ? 'الفصل الثاني' : 'الفصل الأول'),
            ])
            ->recordActions([
                Action::make('select')
                    ->label('تفعيل')
                    ->action(function ($record): void {
                        session(['active_student_id' => $record->id]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
