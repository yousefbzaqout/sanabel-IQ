<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Pages;

use App\Enums\QuestionType;
use App\Filament\Resources\Questions\QuestionResource;
use App\Services\Admin\QuestionOptionsValidator;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['options'] = $this->record->options()
            ->orderBy('order_column')
            ->get()
            ->map(static fn ($option): array => [
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
            ])
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $type = QuestionType::from((string) $data['type']);
        $options = collect($data['options'] ?? [])
            ->map(static fn (array $option): array => [
                'option_text' => (string) ($option['option_text'] ?? ''),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
            ])
            ->values()
            ->all();

        $this->assertValidOptions($type, $options);

        unset($data['options']);

        return $data;
    }

    protected function afterSave(): void
    {
        $options = collect($this->form->getRawState()['options'] ?? [])
            ->values()
            ->all();

        $this->record->options()->delete();

        foreach ($options as $index => $option) {
            $this->record->options()->create([
                'option_text' => (string) ($option['option_text'] ?? ''),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'order_column' => $index,
            ]);
        }
    }

    /**
     * @param  list<array{option_text: string, is_correct: bool}>  $options
     */
    private function assertValidOptions(QuestionType $type, array $options): void
    {
        try {
            QuestionOptionsValidator::validate($type, $options);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError("data.{$field}", $message);
                }
            }

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
