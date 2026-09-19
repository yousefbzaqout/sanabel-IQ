<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemoRequests\Schemas;

use App\Enums\DemoRequestStatus;
use App\Models\DemoRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DemoRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات المدرسة')
                ->schema([
                    TextInput::make('school_name')
                        ->label('اسم المدرسة')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('contact_name')
                        ->label('اسم المسؤول')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('job_title')
                        ->label('المسمى الوظيفي')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('phone')
                        ->label('رقم الجوال')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('seat_range')
                        ->label('نطاق المقاعد')
                        ->formatStateUsing(fn (?string $state): string => DemoRequest::seatRangeOptions()[$state] ?? (string) $state)
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('notes')
                        ->label('ملاحظات مقدم الطلب')
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('متابعة الفريق')
                ->schema([
                    Select::make('status')
                        ->label('الحالة')
                        ->options(DemoRequestStatus::options())
                        ->required(),
                    Textarea::make('admin_notes')
                        ->label('ملاحظات الإدارة')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
