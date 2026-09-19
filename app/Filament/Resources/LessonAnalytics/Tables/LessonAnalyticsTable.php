<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonAnalytics\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonAnalyticsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('نوع الحدث')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('interactiveLesson.title')
                    ->label('الدرس')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('tenant.name')
                    ->label('المدرسة')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
