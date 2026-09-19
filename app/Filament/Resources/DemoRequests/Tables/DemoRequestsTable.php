<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemoRequests\Tables;

use App\Enums\DemoRequestStatus;
use App\Filament\Resources\DemoRequests\Actions\ConvertDemoRequestToTenantAction;
use App\Models\DemoRequest;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DemoRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_name')
                    ->label('المدرسة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('المسؤول')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الجوال')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('seat_range')
                    ->label('المقاعد')
                    ->formatStateUsing(fn (?string $state): string => DemoRequest::seatRangeOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (DemoRequestStatus|string|null $state): string => match (true) {
                        $state instanceof DemoRequestStatus => $state->label(),
                        is_string($state) => DemoRequestStatus::tryFrom($state)?->label() ?? $state,
                        default => '',
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(DemoRequestStatus::options()),
                SelectFilter::make('seat_range')
                    ->label('نطاق المقاعد')
                    ->options(DemoRequest::seatRangeOptions()),
            ])
            ->recordActions([
                ConvertDemoRequestToTenantAction::make(),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
