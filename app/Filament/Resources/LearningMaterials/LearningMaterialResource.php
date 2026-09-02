<?php

declare(strict_types=1);

namespace App\Filament\Resources\LearningMaterials;

use App\Filament\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Filament\Resources\LearningMaterials\Pages\EditLearningMaterial;
use App\Filament\Resources\LearningMaterials\Pages\ListLearningMaterials;
use App\Filament\Resources\LearningMaterials\Schemas\LearningMaterialForm;
use App\Filament\Resources\LearningMaterials\Tables\LearningMaterialsTable;
use App\Models\LearningMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LearningMaterialResource extends Resource
{
    protected static ?string $model = LearningMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'المواد التعليمية';

    protected static ?string $modelLabel = 'مادة تعليمية';

    protected static ?string $pluralModelLabel = 'المواد التعليمية';

    public static function form(Schema $schema): Schema
    {
        return LearningMaterialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningMaterialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningMaterials::route('/'),
            'create' => CreateLearningMaterial::route('/create'),
            'edit' => EditLearningMaterial::route('/{record}/edit'),
        ];
    }
}
