<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('الابن/الابنة')
                    ->relationship(
                        name: 'student',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id()),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
                Select::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                TextInput::make('target_activity_count')
                    ->label('عدد الأنشطة المستهدف')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(5),
                TextInput::make('target_xp')
                    ->label('XP المستهدف')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->default(200),
                DatePicker::make('start_date')
                    ->label('تاريخ البداية')
                    ->required()
                    ->default(now()),
                DatePicker::make('end_date')
                    ->label('تاريخ النهاية')
                    ->required()
                    ->default(now()->addDays(7)),
            ]);
    }
}
