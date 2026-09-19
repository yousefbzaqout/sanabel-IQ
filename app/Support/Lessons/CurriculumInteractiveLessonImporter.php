<?php

declare(strict_types=1);

namespace App\Support\Lessons;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use RuntimeException;

/**
 * Shared idempotent importer for curriculum interactive lessons (6 stations).
 */
final class CurriculumInteractiveLessonImporter
{
    /**
     * @param  array{
     *     key: string,
     *     lesson_key: string,
     *     title: string,
     *     subtitle: string,
     *     quiz_material_title: string,
     *     subject_code: string,
     *     audio_slug: string,
     *     demo_station_labels: array<int, string>,
     *     station_copy: array<int, array{instructions: string, sonbol_prompt: string}>,
     *     variant_tabs: list<array<string, mixed>>,
     *     sequence: array{target_word: string, syllables: list<array{glyph: string, order: int}>, completion_audio_script: string},
     *     structure: array{mode: string, cards: list<array<string, mixed>>},
     *     tracing: array{label: string, path: string, direction: string, complete_audio: string},
     *     discovery: array{intro: string, story_audio: string, items: list<array<string, mixed>>},
     *     demo: array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string, instructions?: string, sonbol_prompt?: string}
     * }  $definition
     */
    public function import(array $definition, string $sourceClass): InteractiveLesson
    {
        $materialTitle = (string) $definition['quiz_material_title'];
        $material = LearningMaterial::query()->where('title', $materialTitle)->first();

        if ($material === null) {
            throw new RuntimeException("Missing LearningMaterial for interactive lesson: {$materialTitle}");
        }

        $gradeLevel = (int) ($definition['grade_level'] ?? 1);

        $lesson = InteractiveLesson::query()->updateOrCreate(
            ['lesson_key' => (string) $definition['lesson_key']],
            [
                'learning_material_id' => $material->id,
                'title' => (string) $definition['title'],
                'subtitle' => (string) $definition['subtitle'],
                'subject_code' => (string) $definition['subject_code'],
                'grade_level' => $gradeLevel,
                'station_count' => 6,
                'status' => 'published',
                'intro_audio_path' => 'audio/grade'.$gradeLevel.'/lessons/'.$definition['audio_slug'].'/intro.mp3',
                'meta' => [
                    'legacy_key' => (string) $definition['key'],
                    'source' => $sourceClass,
                    'quiz_material_title' => $materialTitle,
                ],
            ],
        );

        foreach ($this->stationPayloads($definition, $material) as $payload) {
            InteractiveLessonStation::query()->updateOrCreate(
                [
                    'interactive_lesson_id' => $lesson->id,
                    'station_number' => $payload['station_number'],
                ],
                [
                    'station_type' => $payload['station_type'],
                    'title' => $payload['title'],
                    'instructions' => $payload['instructions'],
                    'sonbol_prompt' => $payload['sonbol_prompt'],
                    'config' => $payload['config'],
                    'assets' => $payload['assets'],
                    'is_skippable' => false,
                    'order_column' => $payload['station_number'],
                ],
            );
        }

        return $lesson->fresh(['stations']) ?? $lesson;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<array<string, mixed>>
     */
    private function stationPayloads(array $definition, LearningMaterial $material): array
    {
        /** @var array<int, string> $labels */
        $labels = $definition['demo_station_labels'] ?? [];
        /** @var array<int, array{instructions: string, sonbol_prompt: string}> $copy */
        $copy = $definition['station_copy'] ?? [];
        $slug = (string) $definition['audio_slug'];
        $base = "audio/grade1/lessons/{$slug}";

        /** @var list<array<string, mixed>> $tabs */
        $tabs = $definition['variant_tabs'] ?? [];
        /** @var array{target_word: string, syllables: list<array{glyph: string, order: int}>, completion_audio_script: string} $sequence */
        $sequence = $definition['sequence'];
        /** @var array{mode: string, cards: list<array<string, mixed>>} $structure */
        $structure = $definition['structure'];
        /** @var array{label: string, path: string, direction: string, complete_audio: string} $tracing */
        $tracing = $definition['tracing'];
        /** @var array{intro: string, story_audio: string, items: list<array<string, mixed>>} $discovery */
        $discovery = $definition['discovery'];
        /** @var array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string} $demo */
        $demo = $definition['demo'];

        return [
            [
                'station_number' => 1,
                'station_type' => 'variant_matrix',
                'title' => $labels[1] ?? 'المحطة ١',
                'instructions' => $copy[1]['instructions'] ?? '',
                'sonbol_prompt' => $copy[1]['sonbol_prompt'] ?? '',
                'config' => [
                    'tabs' => array_map(static function (array $tab) use ($base): array {
                        return [
                            'glyph' => (string) $tab['glyph'],
                            'label' => (string) $tab['label'],
                            'audio_script' => (string) ($tab['audio_script'] ?? ''),
                            'audio_path' => $tab['audio_path'] ?? $base.'/tab-'.md5((string) $tab['glyph']).'.mp3',
                            'cards' => array_map(static fn (array $card): array => [
                                'word' => (string) ($card['word'] ?? ''),
                                'emoji' => (string) ($card['emoji'] ?? ''),
                                'highlight' => (string) ($card['highlight'] ?? ''),
                                'audio_script' => (string) ($card['audio_script'] ?? ''),
                                'audio_path' => $card['audio_path'] ?? $base.'/card-'.md5((string) ($card['word'] ?? '')).'.mp3',
                                'image_path' => $card['image_path'] ?? null,
                            ], $tab['cards'] ?? []),
                        ];
                    }, $tabs),
                    'voice_targets' => array_values(array_map(
                        static fn (array $tab): string => (string) $tab['glyph'],
                        $tabs,
                    )),
                ],
                'assets' => ['neural_audio' => true],
            ],
            [
                'station_number' => 2,
                'station_type' => 'sequence_pop',
                'title' => $labels[2] ?? 'المحطة ٢',
                'instructions' => $copy[2]['instructions'] ?? '',
                'sonbol_prompt' => $copy[2]['sonbol_prompt'] ?? '',
                'config' => [
                    'target_word' => (string) $sequence['target_word'],
                    'syllables' => array_map(static fn (array $syllable): array => [
                        'glyph' => (string) $syllable['glyph'],
                        'order' => (int) $syllable['order'],
                        'audio_path' => $base.'/seq-'.$syllable['order'].'.mp3',
                    ], $sequence['syllables']),
                    'distractors' => [],
                    'completion_audio_script' => (string) $sequence['completion_audio_script'],
                    'completion_audio_path' => $base.'/seq-complete.mp3',
                ],
                'assets' => null,
            ],
            [
                'station_number' => 3,
                'station_type' => 'structure_cards',
                'title' => $labels[3] ?? 'المحطة ٣',
                'instructions' => $copy[3]['instructions'] ?? '',
                'sonbol_prompt' => $copy[3]['sonbol_prompt'] ?? '',
                'config' => [
                    'mode' => (string) $structure['mode'],
                    'cards' => array_map(static fn (array $card): array => [
                        'id' => (string) $card['id'],
                        'label' => (string) $card['label'],
                        'display_word' => (string) $card['display_word'],
                        'parts' => $card['parts'],
                        'audio_script' => (string) ($card['audio_script'] ?? ''),
                        'audio_path' => $base.'/structure-'.$card['id'].'.mp3',
                    ], $structure['cards']),
                ],
                'assets' => null,
            ],
            [
                'station_number' => 4,
                'station_type' => 'trace_canvas',
                'title' => $labels[4] ?? 'المحطة ٤',
                'instructions' => (string) $tracing['label'],
                'sonbol_prompt' => $copy[4]['sonbol_prompt'] ?? '',
                'config' => [
                    'view_box' => (string) ($tracing['view_box'] ?? '0 0 140 140'),
                    'paths' => [[
                        'id' => 'stroke-1',
                        'd' => (string) $tracing['path'],
                        'order' => 1,
                        'direction' => (string) $tracing['direction'],
                    ]],
                    'checkpoints' => [
                        ['t' => 0.0, 'label' => 'start'],
                        ['t' => 0.5, 'label' => 'mid'],
                        ['t' => 1.0, 'label' => 'end'],
                    ],
                    'tolerance_px' => 18,
                    'complete_audio_script' => (string) $tracing['complete_audio'],
                    'complete_audio_path' => $base.'/trace-complete.mp3',
                ],
                'assets' => null,
            ],
            [
                'station_number' => 5,
                'station_type' => 'scratch_discover',
                'title' => $labels[5] ?? 'المحطة ٥',
                'instructions' => (string) $discovery['intro'],
                'sonbol_prompt' => (string) $discovery['story_audio'],
                'config' => [
                    'background_image' => "images/lessons/{$slug}/bg.webp",
                    'overlay_image' => "images/lessons/{$slug}/sand-overlay.webp",
                    'overlay_mode' => 'sand',
                    'reveal_threshold' => 0.55,
                    'story_audio_script' => (string) $discovery['story_audio'],
                    'story_audio_path' => $base.'/story.mp3',
                    'hotspots' => array_map(static fn (array $item): array => [
                        'id' => (string) $item['id'],
                        'label' => (string) $item['label'],
                        'emoji' => (string) $item['emoji'],
                        'correct' => (bool) $item['correct'],
                        'audio_script' => (string) ($item['audio_script'] ?? ''),
                        'audio_path' => $base.'/hotspot-'.$item['id'].'.mp3',
                        'x' => null,
                        'y' => null,
                        'r' => null,
                    ], $discovery['items']),
                ],
                'assets' => ['overlay_mode' => 'sand'],
            ],
            [
                'station_number' => 6,
                'station_type' => 'guided_demo',
                'title' => $labels[6] ?? 'المحطة ٦',
                'instructions' => $copy[6]['instructions'] ?? 'شاهد الشرح ثم انتقل للاختبار.',
                'sonbol_prompt' => $copy[6]['sonbol_prompt'] ?? 'هيا نشرح معاً!',
                'config' => [
                    'question_text' => (string) $demo['question'],
                    'parts' => $demo['parts'],
                    'explain_script' => (string) $demo['explain_audio'],
                    'explain_audio_path' => $base.'/guided-demo.mp3',
                    'linked_question_id' => null,
                    'cta_label' => (string) $demo['cta'],
                    'quiz_learning_material_id' => $material->id,
                ],
                'assets' => null,
            ],
        ];
    }
}
