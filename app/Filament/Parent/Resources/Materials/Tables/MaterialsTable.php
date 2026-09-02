<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                TextColumn::make('student.name')
                    ->label('الابن/الابنة'),
                TextColumn::make('type')
                    ->label('النوع'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
