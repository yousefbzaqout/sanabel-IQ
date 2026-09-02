<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Materials;

use App\Filament\Parent\Resources\Materials\Pages\CreateMaterial;
use App\Filament\Parent\Resources\Materials\Pages\ListMaterials;
use App\Filament\Parent\Resources\Materials\Schemas\MaterialForm;
use App\Filament\Parent\Resources\Materials\Tables\MaterialsTable;
use App\Models\ParentMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    protected static ?string $model = ParentMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'المواد التعليمية';

    protected static ?string $modelLabel = 'مادة';

    protected static ?string $pluralModelLabel = 'المواد التعليمية';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MaterialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $userId = auth()->id();

        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $userId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterials::route('/'),
            'create' => CreateMaterial::route('/create'),
        ];
    }
}
