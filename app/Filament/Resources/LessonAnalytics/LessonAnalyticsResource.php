<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonAnalytics;

use App\Filament\Concerns\TenantScopedFilamentResource;
use App\Filament\Resources\LessonAnalytics\Pages\ListLessonAnalytics;
use App\Filament\Resources\LessonAnalytics\Tables\LessonAnalyticsTable;
use App\Models\LessonAnalytic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LessonAnalyticsResource extends Resource
{
    use TenantScopedFilamentResource;

    protected static ?string $model = LessonAnalytic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'تحليلات الدروس';

    protected static ?string $modelLabel = 'حدث تحليلي';

    protected static ?string $pluralModelLabel = 'تحليلات الدروس';

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return LessonAnalyticsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonAnalytics::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
