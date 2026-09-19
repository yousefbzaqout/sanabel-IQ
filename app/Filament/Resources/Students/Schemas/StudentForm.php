<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('اسم الطالب')
                ->required()
                ->maxLength(255),
            Select::make('user_id')
                ->label('ولي الأمر / الحساب التابع')
                ->options(function (): array {
                    $tenantId = TenantContext::id();
                    $query = User::query()->withoutGlobalScope('tenant');

                    if ($tenantId !== null) {
                        $query->where('tenant_id', $tenantId);
                    }

                    return $query->pluck('name', 'id')->all();
                })
                ->required()
                ->searchable(),
            Select::make('grade_level')
                ->label('الصف الدراسي')
                ->options([
                    1 => 'الصف الأول',
                    2 => 'الصف الثاني',
                    3 => 'الصف الثالث',
                    4 => 'الصف الرابع',
                    5 => 'الصف الخامس',
                    6 => 'الصف السادس',
                ])
                ->default(1)
                ->required(),
            Select::make('school_term')
                ->label('الفصل الدراسي')
                ->options([
                    1 => 'الفصل الأول',
                    2 => 'الفصل الثاني',
                ])
                ->default(1)
                ->required(),
        ]);
    }
}
