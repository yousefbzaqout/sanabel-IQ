<?php

declare(strict_types=1);

namespace App\Support\Lessons;

use App\Models\InteractiveLesson;
use App\Services\Lessons\LessonEngineCache;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

final class InteractiveLessonCatalog
{
    /**
     * @return array<string, mixed>
     */
    public static function get(string $lessonKey): array
    {
        return LessonEngineCache::rememberLessonPayload($lessonKey, function () use ($lessonKey): array {
            $lesson = self::findLesson($lessonKey);

            if ($lesson !== null) {
                $payload = self::fromDatabase($lesson);

                if ($payload['lesson_key'] !== $lessonKey) {
                    Cache::put(
                        LessonEngineCache::lessonPayloadKey((string) $payload['lesson_key']),
                        $payload,
                        LessonEngineCache::TTL_SECONDS,
                    );
                }

                return $payload;
            }

            return self::fallback($lessonKey);
        });
    }

    public static function exists(string $lessonKey): bool
    {
        if (Cache::has(LessonEngineCache::lessonPayloadKey($lessonKey))) {
            return true;
        }

        if (self::findLesson($lessonKey) !== null) {
            return true;
        }

        return match ($lessonKey) {
            LetterRaaLessonDefinition::KEY,
            LetterRaaInteractiveLessonImporter::LESSON_KEY => true,
            default => false,
        };
    }

    private static function findLesson(string $lessonKey): ?InteractiveLesson
    {
        $direct = InteractiveLesson::query()
            ->with(['stations', 'learningMaterial'])
            ->where('lesson_key', $lessonKey)
            ->first();

        if ($direct !== null) {
            return $direct;
        }

        return InteractiveLesson::query()
            ->with(['stations', 'learningMaterial'])
            ->where('meta->legacy_key', $lessonKey)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromDatabase(InteractiveLesson $lesson): array
    {
        /** @var array<string, mixed> $meta */
        $meta = $lesson->meta ?? [];

        $stations = LessonEngineCache::rememberStations($lesson->id, function () use ($lesson): array {
            if (! $lesson->relationLoaded('stations')) {
                $lesson->load('stations');
            }

            $built = [];

            foreach ($lesson->stations as $station) {
                $number = (int) $station->station_number;
                /** @var array<string, mixed> $config */
                $config = $station->config ?? [];

                $built[$number] = [
                    'number' => $number,
                    'type' => (string) $station->station_type,
                    'title' => (string) ($station->title ?? ''),
                    'instructions' => $station->instructions,
                    'sonbol_prompt' => $station->sonbol_prompt,
                    'config' => $config,
                    'assets' => $station->assets,
                ];
            }

            ksort($built);

            return $built;
        });

        $demoStationLabels = [];
        foreach ($stations as $number => $station) {
            $title = (string) ($station['title'] ?? '');
            if ($title !== '') {
                $demoStationLabels[(int) $number] = $title;
            }
        }
        ksort($demoStationLabels);

        $compat = self::compatibilityFromStations($stations, $lesson);

        return array_merge([
            'source' => 'database',
            'interactive_lesson_id' => $lesson->id,
            'lesson_key' => $lesson->lesson_key,
            'key' => (string) ($meta['legacy_key'] ?? $lesson->lesson_key),
            'title' => $lesson->title,
            'subtitle' => $lesson->subtitle,
            'subject_code' => $lesson->subject_code,
            'grade_level' => $lesson->grade_level,
            'station_count' => $lesson->station_count,
            'status' => $lesson->status,
            'intro_audio_path' => $lesson->intro_audio_path,
            'meta' => $meta,
            'quiz_material_title' => (string) ($meta['quiz_material_title']
                ?? $lesson->learningMaterial?->title
                ?? ''),
            'quiz_learning_material_id' => $lesson->learning_material_id,
            'demo_station_labels' => $demoStationLabels,
            'stations' => $stations,
        ], $compat);
    }

    /**
     * Flatten station JSONB into the legacy keys expected by Blade/Alpine runners.
     *
     * @param  array<int, array<string, mixed>>  $stations
     * @return array<string, mixed>
     */
    private static function compatibilityFromStations(array $stations, InteractiveLesson $lesson): array
    {
        $variant = $stations[1]['config'] ?? [];
        $sequence = $stations[2]['config'] ?? [];
        $structure = $stations[3]['config'] ?? [];
        $trace = $stations[4]['config'] ?? [];
        $scratch = $stations[5]['config'] ?? [];
        $guided = $stations[6]['config'] ?? [];

        /** @var list<array<string, mixed>> $tabs */
        $tabs = $variant['tabs'] ?? [];

        $diacriticTabs = array_map(static function (array $tab): array {
            /** @var list<array<string, mixed>> $cards */
            $cards = $tab['cards'] ?? [];

            return [
                'glyph' => (string) ($tab['glyph'] ?? ''),
                'label' => (string) ($tab['label'] ?? ''),
                'cards' => array_map(static fn (array $card): array => [
                    'word' => (string) ($card['word'] ?? ''),
                    'emoji' => (string) ($card['emoji'] ?? ''),
                    'highlight' => (string) ($card['highlight'] ?? ''),
                    'audio' => (string) ($card['audio_script'] ?? $card['audio'] ?? ''),
                ], $cards),
            ];
        }, $tabs);

        /** @var list<array<string, mixed>> $structureCards */
        $structureCards = $structure['cards'] ?? [];

        $positions = array_map(static fn (array $card): array => [
            'id' => (string) ($card['id'] ?? ''),
            'label' => (string) ($card['label'] ?? ''),
            'word' => (string) ($card['display_word'] ?? $card['word'] ?? ''),
            'parts' => $card['parts'] ?? [],
            'audio' => (string) ($card['audio_script'] ?? $card['audio'] ?? ''),
        ], $structureCards);

        /** @var list<array<string, mixed>> $paths */
        $paths = $trace['paths'] ?? [];
        $firstPath = $paths[0]['d'] ?? '';

        /** @var list<array<string, mixed>> $hotspots */
        $hotspots = $scratch['hotspots'] ?? [];

        $discoveryItems = array_map(static fn (array $item): array => [
            'id' => (string) ($item['id'] ?? ''),
            'label' => (string) ($item['label'] ?? ''),
            'emoji' => (string) ($item['emoji'] ?? ''),
            'correct' => (bool) ($item['correct'] ?? false),
            'audio' => (string) ($item['audio_script'] ?? $item['audio'] ?? ''),
        ], $hotspots);

        $stateLabels = [];
        foreach ([1 => 1, 2 => 3, 3 => 4, 4 => 5, 5 => 6] as $state => $stationNumber) {
            if (isset($stations[$stationNumber]['title']) && $stations[$stationNumber]['title'] !== '') {
                $stateLabels[$state] = (string) $stations[$stationNumber]['title'];
            }
        }

        return [
            'state_labels' => $stateLabels !== [] ? $stateLabels : [
                1 => 'الحركات الثلاث',
                2 => 'مواقع الحرف',
                3 => 'التتبع بالإصبع',
                4 => 'الاستكشاف البيئي',
                5 => 'المعلم الصغير',
            ],
            'diacritic_tabs' => $diacriticTabs,
            'syllables' => $sequence['syllables'] ?? [],
            'target_word' => (string) ($sequence['target_word'] ?? ''),
            'word_complete_audio' => (string) ($sequence['completion_audio_script'] ?? ''),
            'positions' => $positions,
            'tracing' => [
                'label' => (string) ($stations[4]['instructions'] ?? ''),
                'path' => (string) $firstPath,
                'complete_audio' => (string) ($trace['complete_audio_script'] ?? $trace['complete_audio'] ?? ''),
                'view_box' => (string) ($trace['view_box'] ?? '0 0 140 140'),
            ],
            'discovery' => [
                'intro' => (string) ($stations[5]['instructions'] ?? $scratch['intro'] ?? ''),
                'story_audio' => (string) ($scratch['story_audio_script'] ?? $stations[5]['sonbol_prompt'] ?? ''),
                'items' => $discoveryItems,
            ],
            'demo' => [
                'question' => (string) ($guided['question_text'] ?? $guided['question'] ?? ''),
                'parts' => $guided['parts'] ?? [],
                'explain_audio' => (string) ($guided['explain_script'] ?? $guided['explain_audio'] ?? ''),
                'cta' => (string) ($guided['cta_label'] ?? $guided['cta'] ?? ''),
            ],
            'quiz_learning_material_id' => (int) ($guided['quiz_learning_material_id']
                ?? $lesson->learning_material_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fallback(string $lessonKey): array
    {
        $resolvedKey = match ($lessonKey) {
            LetterRaaLessonDefinition::KEY,
            LetterRaaInteractiveLessonImporter::LESSON_KEY => LetterRaaLessonDefinition::KEY,
            default => null,
        };

        if ($resolvedKey === null) {
            throw new InvalidArgumentException("Unknown interactive lesson [{$lessonKey}].");
        }

        $definition = LetterRaaLessonDefinition::definition();

        return array_merge($definition, [
            'source' => 'fallback',
            'lesson_key' => $lessonKey === LetterRaaInteractiveLessonImporter::LESSON_KEY
                ? LetterRaaInteractiveLessonImporter::LESSON_KEY
                : LetterRaaLessonDefinition::KEY,
            'stations' => self::stationsFromLegacyDefinition($definition),
            'syllables' => [
                ['glyph' => 'رَ', 'order' => 1],
                ['glyph' => 'مَ', 'order' => 2],
                ['glyph' => 'لْ', 'order' => 3],
            ],
            'target_word' => 'رَمَل',
            'word_complete_audio' => 'أحسنت! كوّنت كلمة رَمَل',
            'quiz_learning_material_id' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, array<string, mixed>>
     */
    private static function stationsFromLegacyDefinition(array $definition): array
    {
        /** @var array<int, string> $labels */
        $labels = $definition['demo_station_labels'] ?? [];

        return [
            1 => [
                'number' => 1,
                'type' => 'variant_matrix',
                'title' => $labels[1] ?? 'الحركات الثلاث',
                'instructions' => null,
                'sonbol_prompt' => null,
                'config' => [
                    'tabs' => $definition['diacritic_tabs'] ?? [],
                    'voice_targets' => array_values(array_map(
                        static fn (array $tab): string => (string) $tab['glyph'],
                        $definition['diacritic_tabs'] ?? [],
                    )),
                ],
                'assets' => null,
            ],
            2 => [
                'number' => 2,
                'type' => 'sequence_pop',
                'title' => $labels[2] ?? 'فرقعة الفقاعات',
                'instructions' => null,
                'sonbol_prompt' => null,
                'config' => [
                    'target_word' => 'رَمَل',
                    'syllables' => [
                        ['glyph' => 'رَ', 'order' => 1],
                        ['glyph' => 'مَ', 'order' => 2],
                        ['glyph' => 'لْ', 'order' => 3],
                    ],
                ],
                'assets' => null,
            ],
            3 => [
                'number' => 3,
                'type' => 'structure_cards',
                'title' => $labels[3] ?? 'مواقع الحرف',
                'instructions' => null,
                'sonbol_prompt' => null,
                'config' => [
                    'mode' => 'letter_position',
                    'cards' => array_values(array_map(
                        static function (array $position, int $index): array {
                            $ids = ['begin', 'middle', 'end'];

                            return [
                                'id' => $ids[$index] ?? 'position-'.($index + 1),
                                'label' => $position['label'],
                                'display_word' => $position['word'],
                                'parts' => $position['parts'],
                                'audio_script' => $position['audio'],
                            ];
                        },
                        $definition['positions'] ?? [],
                        array_keys($definition['positions'] ?? []),
                    )),
                ],
                'assets' => null,
            ],
            4 => [
                'number' => 4,
                'type' => 'trace_canvas',
                'title' => $labels[4] ?? 'التتبع بالإصبع',
                'instructions' => (string) ($definition['tracing']['label'] ?? ''),
                'sonbol_prompt' => null,
                'config' => [
                    'view_box' => '0 0 140 140',
                    'paths' => [
                        [
                            'id' => 'stroke-1',
                            'd' => (string) ($definition['tracing']['path'] ?? ''),
                            'order' => 1,
                        ],
                    ],
                    'complete_audio_script' => (string) ($definition['tracing']['complete_audio'] ?? ''),
                ],
                'assets' => null,
            ],
            5 => [
                'number' => 5,
                'type' => 'scratch_discover',
                'title' => $labels[5] ?? 'الاستكشاف البيئي',
                'instructions' => (string) ($definition['discovery']['intro'] ?? ''),
                'sonbol_prompt' => null,
                'config' => [
                    'story_audio_script' => 'مرحباً! هذه بيارة الرمان. انظر… رُمّان تبدأ بحرف الراء!',
                    'hotspots' => $definition['discovery']['items'] ?? [],
                ],
                'assets' => null,
            ],
            6 => [
                'number' => 6,
                'type' => 'guided_demo',
                'title' => $labels[6] ?? 'المعلم الصغير',
                'instructions' => null,
                'sonbol_prompt' => null,
                'config' => [
                    'question_text' => (string) ($definition['demo']['question'] ?? ''),
                    'parts' => $definition['demo']['parts'] ?? [],
                    'explain_script' => (string) ($definition['demo']['explain_audio'] ?? ''),
                    'cta_label' => (string) ($definition['demo']['cta'] ?? ''),
                ],
                'assets' => null,
            ],
        ];
    }
}
