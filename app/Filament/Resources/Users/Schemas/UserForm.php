<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('البريد')
                ->email()
                ->required()
                ->maxLength(255),
            Select::make('role')
                ->label('الدور')
                ->options([
                    UserRole::Parent->value => 'ولي أمر',
                    UserRole::Student->value => 'طالب',
                    UserRole::TenantAdmin->value => 'مدير مدرسة',
                    UserRole::Teacher->value => 'معلم',
                    UserRole::Admin->value => 'مدير نظام',
                ])
                ->required()
                ->native(false),
            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create'),
        ]);
    }
}
