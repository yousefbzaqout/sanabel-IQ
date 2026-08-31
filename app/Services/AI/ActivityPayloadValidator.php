<?php

declare(strict_types=1);

namespace App\Services\AI;

final class ActivityPayloadValidator
{
    /** @var list<string> */
    private const ALLOWED_TYPES = ['multiple_choice', 'true_false', 'fill_in_the_blank'];

    /**
     * @param  array<string, mixed>  $structured
     */
    public function validate(array $structured): void
    {
        if (! isset($structured['title']) || trim((string) $structured['title']) === '') {
            throw new InvalidActivityPayloadException('Activity title is required.');
        }

        if (! isset($structured['total_xp']) || (int) $structured['total_xp'] < 1) {
            throw new InvalidActivityPayloadException('Activity total_xp must be at least 1.');
        }

        if (! isset($structured['questions']) || ! is_array($structured['questions']) || $structured['questions'] === []) {
            throw new InvalidActivityPayloadException('Activity must include at least one question.');
        }

        foreach (array_values($structured['questions']) as $index => $question) {
            $this->validateQuestion($question, $index);
        }
    }

    private function validateQuestion(mixed $question, int $index): void
    {
        if (! is_array($question)) {
            throw new InvalidActivityPayloadException("Question {$index} must be an object.");
        }

        foreach (['type', 'question', 'options', 'correct_index', 'explanation'] as $field) {
            if (! array_key_exists($field, $question)) {
                throw new InvalidActivityPayloadException("Question {$index} is missing {$field}.");
            }
        }

        if (! in_array($question['type'], self::ALLOWED_TYPES, true)) {
            throw new InvalidActivityPayloadException("Question {$index} has an invalid type.");
        }

        if (trim((string) $question['question']) === '') {
            throw new InvalidActivityPayloadException("Question {$index} text cannot be empty.");
        }

        if (! is_array($question['options']) || count($question['options']) < 2) {
            throw new InvalidActivityPayloadException("Question {$index} must have at least two options.");
        }

        if (! is_numeric($question['correct_index'])) {
            throw new InvalidActivityPayloadException("Question {$index} correct_index must be numeric.");
        }

        $correctIndex = (int) $question['correct_index'];

        if ($correctIndex < 0 || $correctIndex >= count($question['options'])) {
            throw new InvalidActivityPayloadException("Question {$index} correct_index is out of bounds.");
        }

        if (trim((string) $question['explanation']) === '') {
            throw new InvalidActivityPayloadException("Question {$index} explanation cannot be empty.");
        }
    }
}
