<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Lessons\InteractiveStationConfigRepository;

final class ArabicPronunciationScorer
{
    private const DEFAULT_LETTER_TARGETS = ['رَ', 'رُ', 'رِ', 'ر', 'را', 'رو', 'ري'];

    private const DEFAULT_NUMBER_TARGETS = ['1', '2', '3', '١', '٢', '٣'];

    public function __construct(
        private readonly InteractiveStationConfigRepository $stations = new InteractiveStationConfigRepository,
    ) {}

    /**
     * @param  list<string>|null  $allowedTargets
     * @return array{result: string, score: int, feedback: string}
     */
    public function score(string $target, string $transcript, ?array $allowedTargets = null): array
    {
        $normalizedTarget = $this->normalize($target);
        $normalizedTranscript = $this->normalize($transcript);

        if ($normalizedTarget === '' || ! $this->isAllowedTarget($target, $allowedTargets)) {
            return [
                'result' => 'retry',
                'score' => 0,
                'feedback' => 'هدف النطق غير مدعوم',
            ];
        }

        if ($normalizedTranscript === '') {
            return [
                'result' => 'retry',
                'score' => 0,
                'feedback' => 'حاول مرة أخرى يا بطل',
            ];
        }

        if ($this->isNumericTarget($target)) {
            return $this->scoreNumericTarget($target, $normalizedTranscript);
        }

        $hasRaa = str_contains($normalizedTranscript, 'ر')
            || str_contains($normalizedTranscript, 'ra')
            || str_contains($normalizedTranscript, 'r');

        if (! $hasRaa) {
            return [
                'result' => 'retry',
                'score' => 25,
                'feedback' => 'لم أسمع حرف الراء — أعد المحاولة',
            ];
        }

        $vowelScore = $this->vowelAffinity($normalizedTarget, $normalizedTranscript);
        $score = min(100, 55 + $vowelScore);

        if ($score >= 70) {
            return [
                'result' => 'match',
                'score' => $score,
                'feedback' => 'ممتاز! نطقك واضح',
            ];
        }

        return [
            'result' => 'retry',
            'score' => $score,
            'feedback' => 'قريب جداً — حاول مرة أخرى',
        ];
    }

    /**
     * @param  list<string>|null  $allowedTargets
     */
    public function isAllowedTarget(string $target, ?array $allowedTargets = null): bool
    {
        $trimmed = trim($target);
        $pool = $allowedTargets ?? array_merge(self::DEFAULT_LETTER_TARGETS, self::DEFAULT_NUMBER_TARGETS);

        if (in_array($trimmed, $pool, true)) {
            return true;
        }

        $normalizedPool = array_map(fn (string $value): string => $this->normalize($value), $pool);

        return in_array($this->normalize($trimmed), $normalizedPool, true)
            || in_array($this->normalize($trimmed), ['ر', 'را', 'رو', 'ري', '1', '2', '3'], true);
    }

    /**
     * @return list<string>
     */
    public function allowedTargetsForLesson(string $lessonKey): array
    {
        $targets = $this->stations->voiceTargets($lessonKey);

        return $targets !== []
            ? $targets
            : array_merge(self::DEFAULT_LETTER_TARGETS, self::DEFAULT_NUMBER_TARGETS);
    }

    public function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = preg_replace('/[\x{064B}-\x{0652}]/u', '', $text) ?? $text;
        $text = str_replace(['١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٠'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'], $text);

        return $text;
    }

    private function isNumericTarget(string $target): bool
    {
        return (bool) preg_match('/^[1-3١٢٣]$/u', trim($target));
    }

    /**
     * @return array{result: string, score: int, feedback: string}
     */
    private function scoreNumericTarget(string $target, string $normalizedTranscript): array
    {
        $digit = $this->normalize($target);

        $aliases = match ($digit) {
            '1' => ['1', 'واحد', 'wahid', 'one'],
            '2' => ['2', 'اثنان', 'اثنين', 'اثنين', 'ithnan', 'two'],
            '3' => ['3', 'ثلاثة', 'ثلاث', 'thalatha', 'three'],
            default => [$digit],
        };

        foreach ($aliases as $alias) {
            if ($normalizedTranscript === $this->normalize($alias) || str_contains($normalizedTranscript, $this->normalize($alias))) {
                return [
                    'result' => 'match',
                    'score' => 92,
                    'feedback' => 'ممتاز! العدد واضح',
                ];
            }
        }

        return [
            'result' => 'retry',
            'score' => 30,
            'feedback' => 'حاول نطق العدد بوضوح مرة أخرى',
        ];
    }

    private function vowelAffinity(string $target, string $transcript): int
    {
        if (str_contains($transcript, $target) || str_contains($transcript, 'ر'.$target)) {
            return 40;
        }

        if (str_starts_with($target, 'ر') && str_contains($transcript, 'ر')) {
            if (str_contains($target, 'ا') || str_ends_with($transcript, 'ا') || str_contains($transcript, 'را')) {
                return 35;
            }
            if (str_contains($transcript, 'رو') || str_contains($transcript, 'و')) {
                return 30;
            }
            if (str_contains($transcript, 'ري') || str_contains($transcript, 'ي')) {
                return 30;
            }

            return 25;
        }

        return 10;
    }
}
