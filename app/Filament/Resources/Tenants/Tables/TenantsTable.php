<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('المدرسة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('المعرف الفرعي')
                    ->searchable(),
                TextColumn::make('domain')
                    ->label('النطاق المخصص')
                    ->toggleable(),
                TextColumn::make('seat_limit')
                    ->label('سعة المقاعد')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subscription_plan')
                    ->label('الخطة')
                    ->badge(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Tenant::STATUS_ACTIVE => 'نشط',
                        Tenant::STATUS_INACTIVE => 'غير نشط',
                        default => (string) $state,
                    }),
            ])
            ->defaultSort('name', 'asc')
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
