<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Goals;

use App\Filament\Parent\Resources\Goals\Pages\CreateGoal;
use App\Filament\Parent\Resources\Goals\Pages\ListGoals;
use App\Filament\Parent\Resources\Goals\Schemas\GoalForm;
use App\Filament\Parent\Resources\Goals\Tables\GoalsTable;
use App\Models\ParentLearningGoal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoalResource extends Resource
{
    protected static ?string $model = ParentLearningGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'أهداف وتحديات الأسبوع';

    protected static ?string $modelLabel = 'هدف';

    protected static ?string $pluralModelLabel = 'الأهداف';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return GoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoalsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $userId = auth()->id();

        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('parent_id', $userId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoals::route('/'),
            'create' => CreateGoal::route('/create'),
        ];
    }
}
