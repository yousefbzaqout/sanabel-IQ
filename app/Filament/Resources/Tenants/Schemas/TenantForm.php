<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('اسم المدرسة')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('المعرف الفرعي')
                ->required()
                ->maxLength(255),
            TextInput::make('domain')
                ->label('النطاق المخصص')
                ->maxLength(255),
            TextInput::make('logo_path')
                ->label('شعار المدرسة (URL)')
                ->maxLength(500),
            TextInput::make('primary_color')
                ->label('اللون الأساسي')
                ->maxLength(32),
            TextInput::make('contact_email')
                ->label('البريد الإلكتروني للتواصل')
                ->email()
                ->maxLength(255),
            TextInput::make('contact_phone')
                ->label('هاتف التواصل')
                ->maxLength(64),
            TextInput::make('seat_limit')
                ->label('سعة مقاعد الطلاب')
                ->numeric()
                ->default(50)
                ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false)),
            TextInput::make('subscription_plan')
                ->label('خطة الاشتراك')
                ->default('standard')
                ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false)),
            Select::make('status')
                ->label('الحالة')
                ->options([
                    Tenant::STATUS_ACTIVE => 'نشط',
                    Tenant::STATUS_INACTIVE => 'غير نشط',
                ])
                ->default(Tenant::STATUS_ACTIVE)
                ->required()
                ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false)),
        ]);
    }
}
