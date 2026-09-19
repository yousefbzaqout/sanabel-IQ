<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children;

use App\Filament\Parent\Resources\Children\Pages\CreateChild;
use App\Filament\Parent\Resources\Children\Pages\EditChild;
use App\Filament\Parent\Resources\Children\Pages\ListChildren;
use App\Filament\Parent\Resources\Children\Schemas\ChildForm;
use App\Filament\Parent\Resources\Children\Tables\ChildrenTable;
use App\Models\Student;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChildResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'إدارة الأبناء';

    protected static ?string $modelLabel = 'ابن/ابنة';

    protected static ?string $pluralModelLabel = 'الأبناء';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ChildForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChildrenTable::configure($table);
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
            'index' => ListChildren::route('/'),
            'create' => CreateChild::route('/create'),
            'edit' => EditChild::route('/{record}/edit'),
        ];
    }
}
