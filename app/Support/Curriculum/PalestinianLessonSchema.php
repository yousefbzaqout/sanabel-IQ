<?php

declare(strict_types=1);

namespace App\Support\Curriculum;

use InvalidArgumentException;

final class PalestinianLessonSchema
{
    /**
     * @param  array<string, mixed>  $pack
     */
    public static function assertValid(array $pack): void
    {
        foreach (['schema_version', 'lesson_key', 'title', 'material_title', 'phonemes', 'stroke', 'quiz'] as $key) {
            if (! array_key_exists($key, $pack) || $pack[$key] === null || $pack[$key] === '') {
                throw new InvalidArgumentException("Palestinian lesson pack missing required key: {$key}");
            }
        }

        $contentKind = self::contentKind($pack);
        if ($contentKind === 'letter' && (! isset($pack['letter']) || $pack['letter'] === '')) {
            throw new InvalidArgumentException('Palestinian lesson pack missing required key: letter');
        }

        if ($contentKind === 'number') {
            $hasDigit = isset($pack['digit']) && $pack['digit'] !== '';
            $hasDigits = isset($pack['digits']) && is_array($pack['digits']) && $pack['digits'] !== [];
            if (! $hasDigit && ! $hasDigits) {
                throw new InvalidArgumentException('Palestinian math lesson pack missing digit or digits');
            }
        }

        /** @var array<string, mixed> $phonemes */
        $phonemes = $pack['phonemes'];
        if (! isset($phonemes['voice_targets']) || ! is_array($phonemes['voice_targets']) || $phonemes['voice_targets'] === []) {
            throw new InvalidArgumentException('Palestinian lesson pack missing phonemes.voice_targets');
        }

        if (! isset($phonemes['tabs']) || ! is_array($phonemes['tabs']) || $phonemes['tabs'] === []) {
            throw new InvalidArgumentException('Palestinian lesson pack missing phonemes.tabs');
        }

        /** @var array<string, mixed> $stroke */
        $stroke = $pack['stroke'];
        if (! isset($stroke['path']) || ! is_string($stroke['path']) || $stroke['path'] === '') {
            throw new InvalidArgumentException('Palestinian lesson pack missing stroke.path');
        }

        /** @var array<string, mixed> $quiz */
        $quiz = $pack['quiz'];
        if (! isset($quiz['questions']) || ! is_array($quiz['questions']) || $quiz['questions'] === []) {
            throw new InvalidArgumentException('Palestinian lesson pack missing quiz.questions');
        }

        foreach ($quiz['questions'] as $index => $question) {
            if (! is_array($question)) {
                throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}] must be an object");
            }

            if (! isset($question['prompt']) || ! is_string($question['prompt']) || $question['prompt'] === '') {
                throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}] missing prompt");
            }

            if (isset($question['visual_items'])) {
                self::assertVisualItems($question['visual_items'], $index);
            }

            if (isset($question['comparison'])) {
                self::assertComparison($question['comparison'], $index);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pack
     */
    public static function contentKind(array $pack): string
    {
        $explicit = $pack['content_kind'] ?? null;
        if (is_string($explicit) && in_array($explicit, ['letter', 'number'], true)) {
            return $explicit;
        }

        if (isset($pack['digit']) || (isset($pack['digits']) && is_array($pack['digits']))) {
            return 'number';
        }

        return 'letter';
    }

    /**
     * Primary glyph for station copy (letter or Eastern/Western digit).
     *
     * @param  array<string, mixed>  $pack
     */
    public static function primaryGlyph(array $pack): string
    {
        if (isset($pack['digit']) && is_string($pack['digit']) && $pack['digit'] !== '') {
            return $pack['digit'];
        }

        if (isset($pack['digits']) && is_array($pack['digits']) && $pack['digits'] !== []) {
            $last = $pack['digits'][array_key_last($pack['digits'])];

            return (string) $last;
        }

        return (string) ($pack['letter'] ?? '');
    }

    private static function assertVisualItems(mixed $visualItems, int $index): void
    {
        if (! is_array($visualItems)) {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].visual_items must be an object");
        }

        if (! isset($visualItems['emoji']) || ! is_string($visualItems['emoji']) || $visualItems['emoji'] === '') {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].visual_items.emoji required");
        }

        if (! isset($visualItems['count']) || ! is_numeric($visualItems['count']) || (int) $visualItems['count'] < 1) {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].visual_items.count must be >= 1");
        }
    }

    private static function assertComparison(mixed $comparison, int $index): void
    {
        if (! is_array($comparison)) {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].comparison must be an object");
        }

        $operator = $comparison['operator'] ?? null;
        if (! is_string($operator) || ! in_array($operator, ['gt', 'lt', 'eq', 'أكبر', 'أصغر', 'يساوي'], true)) {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].comparison.operator invalid");
        }

        if (! array_key_exists('left', $comparison) || ! array_key_exists('right', $comparison)) {
            throw new InvalidArgumentException("Palestinian lesson pack quiz.questions[{$index}].comparison needs left and right");
        }
    }
}
