<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Pages;

use App\Enums\QuestionType;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use App\Services\Admin\QuestionOptionsValidator;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = QuestionType::from((string) $data['type']);
        $options = collect($data['options'] ?? [])
            ->map(static fn (array $option): array => [
                'option_text' => (string) ($option['option_text'] ?? ''),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
            ])
            ->values()
            ->all();

        QuestionOptionsValidator::validate($type, $options);

        $materialId = (int) $data['learning_material_id'];
        $maxOrder = Question::query()
            ->where('learning_material_id', $materialId)
            ->max('order_column');

        $data['order_column'] = $maxOrder === null ? 0 : ((int) $maxOrder) + 1;
        unset($data['options']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $options = collect($this->form->getState()['options'] ?? [])
            ->values()
            ->all();

        foreach ($options as $index => $option) {
            $this->record->options()->create([
                'option_text' => (string) ($option['option_text'] ?? ''),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'order_column' => $index,
            ]);
        }
    }
}
