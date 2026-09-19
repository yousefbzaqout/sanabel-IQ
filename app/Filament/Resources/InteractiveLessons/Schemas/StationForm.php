<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\Schemas;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class StationForm
{
    /**
     * @return array<string, string>
     */
    public static function stationTypeOptions(): array
    {
        return [
            'variant_matrix' => 'variant_matrix — مصفوفة التنويعات',
            'sequence_pop' => 'sequence_pop — فرقعة التسلسل',
            'structure_cards' => 'structure_cards — بطاقات البنية',
            'trace_canvas' => 'trace_canvas — لوحة التتبع',
            'scratch_discover' => 'scratch_discover — كشف بالمسح',
            'guided_demo' => 'guided_demo — محاكاة المعلم',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('station_number')
                    ->label('رقم المحطة')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(6),
                Select::make('station_type')
                    ->label('نوع المحطة')
                    ->options(self::stationTypeOptions())
                    ->required()
                    ->native(false)
                    ->live(),
                TextInput::make('title')
                    ->label('عنوان المحطة')
                    ->maxLength(255),
                Textarea::make('instructions')
                    ->label('التعليمات')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('sonbol_prompt')
                    ->label('رسالة سنبل')
                    ->rows(2)
                    ->columnSpanFull(),
                Toggle::make('is_skippable')
                    ->label('قابلة للتخطّي')
                    ->default(false),
                TextInput::make('order_column')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
                Section::make('إعدادات المحطة (config)')
                    ->description('الحقول تتغيّر حسب نوع المحطة')
                    ->schema(self::configFields())
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component|Field>
     */
    private static function configFields(): array
    {
        return [
            Repeater::make('config.tabs')
                ->label('التبويبات')
                ->visible(fn (Get $get): bool => $get('station_type') === 'variant_matrix')
                ->schema([
                    TextInput::make('glyph')->label('الرمز')->required(),
                    TextInput::make('label')->label('التسمية')->required(),
                    TextInput::make('audio_script')->label('نص الصوت'),
                    TextInput::make('audio_path')->label('مسار الصوت'),
                    Repeater::make('cards')
                        ->label('البطاقات')
                        ->schema([
                            TextInput::make('word')->label('الكلمة')->required(),
                            TextInput::make('emoji')->label('إيموجي'),
                            TextInput::make('highlight')->label('التمييز'),
                            TextInput::make('audio_script')->label('نص الصوت'),
                            TextInput::make('audio_path')->label('مسار الصوت'),
                        ])
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),
            TextInput::make('config.voice_targets')
                ->label('أهداف النطق (مفصولة بفاصلة)')
                ->visible(fn (Get $get): bool => $get('station_type') === 'variant_matrix')
                ->helperText('مثال: رَ,رُ,رِ')
                ->formatStateUsing(function (mixed $state): string {
                    if (is_array($state)) {
                        return implode(',', $state);
                    }

                    return is_string($state) ? $state : '';
                })
                ->dehydrateStateUsing(function (mixed $state): array {
                    if (is_array($state)) {
                        return array_values(array_filter(array_map(
                            static fn (mixed $value): string => trim((string) $value),
                            $state,
                        )));
                    }

                    if (! is_string($state) || trim($state) === '') {
                        return [];
                    }

                    return array_values(array_filter(array_map('trim', explode(',', $state))));
                }),

            TextInput::make('config.target_word')
                ->label('الكلمة/التسلسل الهدف')
                ->visible(fn (Get $get): bool => $get('station_type') === 'sequence_pop'),
            Repeater::make('config.syllables')
                ->label('عناصر التسلسل')
                ->visible(fn (Get $get): bool => $get('station_type') === 'sequence_pop')
                ->schema([
                    TextInput::make('glyph')->label('الرمز')->required(),
                    TextInput::make('order')->label('الترتيب')->numeric()->required()->minValue(1),
                    TextInput::make('audio_path')->label('مسار الصوت'),
                ])
                ->defaultItems(3)
                ->columnSpanFull(),
            TextInput::make('config.completion_audio_script')
                ->label('نص صوت الإكمال')
                ->visible(fn (Get $get): bool => $get('station_type') === 'sequence_pop')
                ->columnSpanFull(),

            Select::make('config.mode')
                ->label('نمط البطاقات')
                ->options([
                    'letter_position' => 'مواقع الحرف',
                    'quantity_representation' => 'تمثيل الكمية',
                ])
                ->visible(fn (Get $get): bool => $get('station_type') === 'structure_cards'),
            Repeater::make('config.cards')
                ->label('البطاقات البنيوية')
                ->visible(fn (Get $get): bool => $get('station_type') === 'structure_cards')
                ->schema([
                    TextInput::make('id')->label('المعرّف')->required(),
                    TextInput::make('label')->label('التسمية')->required(),
                    TextInput::make('display_word')->label('العرض'),
                    TextInput::make('audio_script')->label('نص الصوت'),
                    Repeater::make('parts')
                        ->label('الأجزاء')
                        ->schema([
                            TextInput::make('text')->label('النص')->required(),
                            Toggle::make('highlight')->label('تمييز')->default(false),
                        ])
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),

            TextInput::make('config.view_box')
                ->label('viewBox')
                ->default('0 0 140 140')
                ->visible(fn (Get $get): bool => $get('station_type') === 'trace_canvas'),
            Repeater::make('config.paths')
                ->label('مسارات SVG')
                ->visible(fn (Get $get): bool => $get('station_type') === 'trace_canvas')
                ->schema([
                    TextInput::make('id')->label('المعرّف')->default('stroke-1'),
                    Textarea::make('d')->label('مسار d')->rows(3)->required()->columnSpanFull(),
                    TextInput::make('order')->label('الترتيب')->numeric()->default(1),
                    TextInput::make('direction')->label('الاتجاه'),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),
            Repeater::make('config.checkpoints')
                ->label('نقاط التحقق')
                ->visible(fn (Get $get): bool => $get('station_type') === 'trace_canvas')
                ->schema([
                    TextInput::make('t')->label('t')->numeric()->required(),
                    TextInput::make('label')->label('التسمية'),
                ])
                ->defaultItems(0)
                ->columnSpanFull(),
            TextInput::make('config.complete_audio_script')
                ->label('نص صوت الإكمال')
                ->visible(fn (Get $get): bool => $get('station_type') === 'trace_canvas')
                ->columnSpanFull(),

            TextInput::make('config.story_audio_script')
                ->label('قصة سنبل الصوتية')
                ->visible(fn (Get $get): bool => $get('station_type') === 'scratch_discover')
                ->columnSpanFull(),
            TextInput::make('config.overlay_mode')
                ->label('نمط الطبقة')
                ->default('sand')
                ->visible(fn (Get $get): bool => $get('station_type') === 'scratch_discover'),
            TextInput::make('config.reveal_threshold')
                ->label('عتبة الكشف')
                ->numeric()
                ->default(0.55)
                ->visible(fn (Get $get): bool => $get('station_type') === 'scratch_discover'),
            Repeater::make('config.hotspots')
                ->label('نقاط الاكتشاف')
                ->visible(fn (Get $get): bool => $get('station_type') === 'scratch_discover')
                ->schema([
                    TextInput::make('id')->label('المعرّف')->required(),
                    TextInput::make('label')->label('التسمية')->required(),
                    TextInput::make('emoji')->label('إيموجي'),
                    Toggle::make('correct')->label('صحيح')->default(true),
                    TextInput::make('audio_script')->label('نص الصوت'),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),

            Textarea::make('config.question_text')
                ->label('نص السؤال')
                ->rows(2)
                ->visible(fn (Get $get): bool => $get('station_type') === 'guided_demo')
                ->columnSpanFull(),
            Repeater::make('config.parts')
                ->label('أجزاء العرض')
                ->visible(fn (Get $get): bool => $get('station_type') === 'guided_demo')
                ->schema([
                    TextInput::make('text')->label('النص')->required(),
                    Toggle::make('highlight')->label('تمييز')->default(false),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),
            Textarea::make('config.explain_script')
                ->label('سيناريو الشرح')
                ->rows(3)
                ->visible(fn (Get $get): bool => $get('station_type') === 'guided_demo')
                ->columnSpanFull(),
            TextInput::make('config.cta_label')
                ->label('نص زر الاختبار')
                ->visible(fn (Get $get): bool => $get('station_type') === 'guided_demo')
                ->columnSpanFull(),
            TextInput::make('config.quiz_learning_material_id')
                ->label('معرّف مادة الاختبار')
                ->numeric()
                ->visible(fn (Get $get): bool => $get('station_type') === 'guided_demo'),
        ];
    }
}
