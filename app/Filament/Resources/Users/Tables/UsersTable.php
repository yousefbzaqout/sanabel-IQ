<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (UserRole|string|null $state): string => match ($state instanceof UserRole ? $state : UserRole::tryFrom((string) $state)) {
                        UserRole::Admin => 'مدير نظام',
                        UserRole::TenantAdmin => 'مدير مدرسة',
                        UserRole::Teacher => 'معلم',
                        UserRole::Parent => 'ولي أمر',
                        UserRole::Student => 'طالب',
                        default => (string) $state,
                    }),
                TextColumn::make('tenant.name')
                    ->label('المدرسة')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('أُنشئ')
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
