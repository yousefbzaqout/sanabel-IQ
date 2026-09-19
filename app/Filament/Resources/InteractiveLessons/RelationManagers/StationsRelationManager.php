<?php

declare(strict_types=1);

namespace App\Filament\Resources\InteractiveLessons\RelationManagers;

use App\Filament\Resources\InteractiveLessons\Schemas\StationForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StationsRelationManager extends RelationManager
{
    protected static string $relationship = 'stations';

    protected static ?string $title = 'محطات الدرس';

    public function form(Schema $schema): Schema
    {
        return StationForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('station_number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('station_type')
                    ->label('النوع')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                IconColumn::make('is_skippable')
                    ->label('تخطّي')
                    ->boolean(),
                TextColumn::make('order_column')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['config'] = is_array($data['config'] ?? null) ? $data['config'] : [];
                        $data['assets'] = $data['assets'] ?? null;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['config'] = is_array($data['config'] ?? null) ? $data['config'] : [];

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
