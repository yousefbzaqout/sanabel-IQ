<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemoRequests;

use App\Filament\Resources\DemoRequests\Pages\EditDemoRequest;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Filament\Resources\DemoRequests\Pages\ViewDemoRequest;
use App\Filament\Resources\DemoRequests\Schemas\DemoRequestForm;
use App\Filament\Resources\DemoRequests\Tables\DemoRequestsTable;
use App\Models\DemoRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DemoRequestResource extends Resource
{
    protected static ?string $model = DemoRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'عمليات المبيعات';

    protected static ?string $navigationLabel = 'طلبات العروض التجريبية';

    protected static ?string $modelLabel = 'طلب عرض تجريبي';

    protected static ?string $pluralModelLabel = 'طلبات العروض التجريبية';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return DemoRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemoRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemoRequests::route('/'),
            'view' => ViewDemoRequest::route('/{record}'),
            'edit' => EditDemoRequest::route('/{record}/edit'),
        ];
    }
}
