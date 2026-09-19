<?php

declare(strict_types=1);

namespace App\Services\Lessons;

use App\Models\InteractiveLesson;
use App\Models\MasteryConcept;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class LessonEngineCache
{
    public const CONCEPTS_ALL = 'concepts:all';

    public const TTL_SECONDS = 3600;

    public static function stationsKey(int $lessonId): string
    {
        return "lesson:{$lessonId}:stations";
    }

    public static function lessonPayloadKey(string $lessonKey): string
    {
        return "lesson:key:{$lessonKey}:payload";
    }

    /**
     * @param  Closure(): array<int, array<string, mixed>>  $callback
     * @return array<int, array<string, mixed>>
     */
    public static function rememberStations(int $lessonId, Closure $callback): array
    {
        /** @var array<int, array<string, mixed>> */
        return Cache::remember(self::stationsKey($lessonId), self::TTL_SECONDS, $callback);
    }

    /**
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public static function rememberLessonPayload(string $lessonKey, Closure $callback): array
    {
        /** @var array<string, mixed> */
        return Cache::remember(self::lessonPayloadKey($lessonKey), self::TTL_SECONDS, $callback);
    }

    /**
     * @return Collection<string, MasteryConcept>
     */
    public static function allConcepts(): Collection
    {
        /** @var Collection<string, MasteryConcept> */
        return Cache::remember(self::CONCEPTS_ALL, self::TTL_SECONDS, static function (): Collection {
            return MasteryConcept::query()->get()->keyBy('code');
        });
    }

    public static function forgetLesson(?InteractiveLesson $lesson): void
    {
        if ($lesson === null) {
            return;
        }

        Cache::forget(self::stationsKey($lesson->id));
        Cache::forget(self::lessonPayloadKey($lesson->lesson_key));

        /** @var array<string, mixed> $meta */
        $meta = $lesson->meta ?? [];
        $legacyKey = $meta['legacy_key'] ?? null;

        if (is_string($legacyKey) && $legacyKey !== '' && $legacyKey !== $lesson->lesson_key) {
            Cache::forget(self::lessonPayloadKey($legacyKey));
        }
    }

    public static function forgetConcepts(): void
    {
        Cache::forget(self::CONCEPTS_ALL);
    }
}
