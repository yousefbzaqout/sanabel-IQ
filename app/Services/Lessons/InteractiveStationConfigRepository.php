<?php

declare(strict_types=1);

namespace App\Services\Lessons;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Support\Lessons\InteractiveLessonCatalog;
use InvalidArgumentException;

final class InteractiveStationConfigRepository
{
    private function findLesson(string $lessonKey): ?InteractiveLesson
    {
        return InteractiveLesson::query()
            ->where(function ($query) use ($lessonKey): void {
                $query->where('lesson_key', $lessonKey)
                    ->orWhere('meta->legacy_key', $lessonKey);
            })
            ->first();
    }

    public function station(string $lessonKey, int $stationNumber): ?InteractiveLessonStation
    {
        $lesson = $this->findLesson($lessonKey);

        if ($lesson === null) {
            return null;
        }

        return $lesson->stations()
            ->where('station_number', $stationNumber)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function config(string $lessonKey, int $stationNumber): array
    {
        $station = $this->station($lessonKey, $stationNumber);

        if ($station !== null) {
            return is_array($station->config) ? $station->config : [];
        }

        try {
            $payload = InteractiveLessonCatalog::get($lessonKey);
        } catch (InvalidArgumentException) {
            return [];
        }

        /** @var array<string, mixed> $stationPayload */
        $stationPayload = $payload['stations'][$stationNumber] ?? [];

        return is_array($stationPayload['config'] ?? null) ? $stationPayload['config'] : [];
    }

    /**
     * @return list<string>
     */
    public function voiceTargets(string $lessonKey): array
    {
        $targets = [];
        $stationOne = $this->config($lessonKey, 1);

        if (isset($stationOne['voice_targets']) && is_array($stationOne['voice_targets'])) {
            foreach ($stationOne['voice_targets'] as $target) {
                $targets[] = (string) $target;
            }
        }

        foreach ($stationOne['tabs'] ?? [] as $tab) {
            if (is_array($tab) && isset($tab['glyph'])) {
                $targets[] = (string) $tab['glyph'];
            }
        }

        $stationTwo = $this->config($lessonKey, 2);
        foreach ($stationTwo['syllables'] ?? [] as $syllable) {
            if (! is_array($syllable) || ! isset($syllable['glyph'])) {
                continue;
            }

            $glyph = trim((string) $syllable['glyph']);
            // Include count/sequence digits (Math) without mixing Arabic syllable bubbles into voice targets.
            if ($glyph !== '' && preg_match('/^[0-9٠-٩]+$/u', $glyph) === 1) {
                $targets[] = $glyph;
            }
        }

        $targets = array_values(array_unique(array_filter(
            array_map(static fn (string $value): string => trim($value), $targets),
            static fn (string $value): bool => $value !== '',
        )));

        return $targets;
    }

    /**
     * @return array{
     *     direction: string,
     *     path_d: string,
     *     tolerance_px: int,
     *     checkpoints: list<array<string, mixed>>,
     *     view_box: string,
     *     feedback_retry: string,
     *     feedback_match: string
     * }
     */
    public function traceGuide(string $lessonKey): array
    {
        $config = $this->config($lessonKey, 4);
        $station = $this->station($lessonKey, 4);

        /** @var list<array<string, mixed>> $paths */
        $paths = $config['paths'] ?? [];
        $firstPath = $paths[0] ?? [];

        return [
            'direction' => (string) ($firstPath['direction'] ?? $config['direction'] ?? 'top_to_bottom_arc'),
            'path_d' => (string) ($firstPath['d'] ?? ''),
            'tolerance_px' => (int) ($config['tolerance_px'] ?? 18),
            'checkpoints' => is_array($config['checkpoints'] ?? null) ? $config['checkpoints'] : [],
            'view_box' => (string) ($config['view_box'] ?? '0 0 140 140'),
            'feedback_retry' => (string) ($station?->instructions
                ?? $config['complete_audio_script']
                ?? 'ابدأ من الأعلى واتبع المسار بهدوء'),
            'feedback_match' => 'رائع! اتجاه الرسم صحيح',
        ];
    }

    public function sonbolPrompt(string $lessonKey, int $stationNumber): ?string
    {
        $station = $this->station($lessonKey, $stationNumber);

        if ($station !== null && filled($station->sonbol_prompt)) {
            return (string) $station->sonbol_prompt;
        }

        try {
            $payload = InteractiveLessonCatalog::get($lessonKey);
        } catch (InvalidArgumentException) {
            return null;
        }

        $prompt = $payload['stations'][$stationNumber]['sonbol_prompt'] ?? null;

        return filled($prompt) ? (string) $prompt : null;
    }
}
