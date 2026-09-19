<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InteractiveLessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('learning_material_id')
                    ->label('المادة التعليمية')
                    ->relationship('learningMaterial', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('lesson_key')
                    ->label('مفتاح الدرس')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('عنوان الدرس')
                    ->required()
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->label('العنوان الفرعي')
                    ->maxLength(255),
                Select::make('subject_code')
                    ->label('رمز المادة')
                    ->options([
                        'AR' => 'AR — العربية',
                        'MATH' => 'MATH — الرياضيات',
                        'ISLAM' => 'ISLAM — التربية الإسلامية',
                        'SOCIAL' => 'SOCIAL — التربية الوطنية',
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('grade_level')
                    ->label('الصف')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(12)
                    ->default(1),
                TextInput::make('station_count')
                    ->label('عدد المحطات')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(6)
                    ->default(6),
                Select::make('status')
                    ->label('حالة النشر')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                    ])
                    ->required()
                    ->native(false)
                    ->default('draft'),
                TextInput::make('intro_audio_path')
                    ->label('مسار صوت المقدمة')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
