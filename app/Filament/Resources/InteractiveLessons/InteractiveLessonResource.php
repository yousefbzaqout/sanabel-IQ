<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons;

use App\Filament\Concerns\TenantScopedFilamentResource;
use App\Filament\Resources\InteractiveLessons\Pages\CreateInteractiveLesson;
use App\Filament\Resources\InteractiveLessons\Pages\EditInteractiveLesson;
use App\Filament\Resources\InteractiveLessons\Pages\ListInteractiveLessons;
use App\Filament\Resources\InteractiveLessons\RelationManagers\StationsRelationManager;
use App\Filament\Resources\InteractiveLessons\Schemas\InteractiveLessonForm;
use App\Filament\Resources\InteractiveLessons\Tables\InteractiveLessonsTable;
use App\Models\InteractiveLesson;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InteractiveLessonResource extends Resource
{
    use TenantScopedFilamentResource;

    protected static ?string $model = InteractiveLesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'الدروس التفاعلية';

    protected static ?string $modelLabel = 'درس تفاعلي';

    protected static ?string $pluralModelLabel = 'الدروس التفاعلية';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return InteractiveLessonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InteractiveLessonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractiveLessons::route('/'),
            'create' => CreateInteractiveLesson::route('/create'),
            'edit' => EditInteractiveLesson::route('/{record}/edit'),
        ];
    }
}
