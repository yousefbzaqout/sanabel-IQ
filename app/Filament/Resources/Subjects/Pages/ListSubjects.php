<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'grade_1' => Tab::make(__('Grade 1'))
                ->query(fn (Builder $query): Builder => $query->where('grade_level', 1)),
            'grade_2' => Tab::make(__('Grade 2'))
                ->query(fn (Builder $query): Builder => $query->where('grade_level', 2)),
            'grade_3' => Tab::make(__('Grade 3'))
                ->query(fn (Builder $query): Builder => $query->where('grade_level', 3)),
            'grade_4' => Tab::make(__('Grade 4'))
                ->query(fn (Builder $query): Builder => $query->where('grade_level', 4)),
            'grade_5' => Tab::make(__('Grade 5'))
                ->query(fn (Builder $query): Builder => $query->where('grade_level', 5)),
        ];
    }
}
