<?php

declare(strict_types=1);

namespace App\Support\Lessons;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use RuntimeException;

/**
 * Maps {@see NumberThreeLessonDefinition} into interactive_lessons + stations (idempotent).
 */
final class NumberThreeInteractiveLessonImporter
{
    public const LESSON_KEY = NumberThreeLessonDefinition::LESSON_KEY;

    public function import(): InteractiveLesson
    {
        $definition = NumberThreeLessonDefinition::definition();

        $material = LearningMaterial::query()
            ->where('title', NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->first();

        if ($material === null) {
            throw new RuntimeException(
                'Missing LearningMaterial for Number 3: '.NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE,
            );
        }

        $lesson = InteractiveLesson::query()->updateOrCreate(
            ['lesson_key' => self::LESSON_KEY],
            [
                'learning_material_id' => $material->id,
                'title' => (string) $definition['title'],
                'subtitle' => (string) $definition['subtitle'],
                'subject_code' => 'MATH',
                'grade_level' => 1,
                'station_count' => 6,
                'status' => 'published',
                'intro_audio_path' => 'audio/grade1/lessons/number-3/intro.mp3',
                'meta' => [
                    'legacy_key' => NumberThreeLessonDefinition::KEY,
                    'source' => NumberThreeLessonDefinition::class,
                    'quiz_material_title' => NumberThreeLessonDefinition::QUIZ_MATERIAL_TITLE,
                    'pilot' => 'math-number-3',
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
     * @return list<array{
     *     station_number: int,
     *     station_type: string,
     *     title: string,
     *     instructions: string|null,
     *     sonbol_prompt: string|null,
     *     config: array<string, mixed>,
     *     assets: array<string, mixed>|null
     * }>
     */
    private function stationPayloads(array $definition, LearningMaterial $material): array
    {
        /** @var array<int, string> $labels */
        $labels = $definition['demo_station_labels'] ?? [];

        /** @var list<array<string, mixed>> $tabs */
        $tabs = $definition['variant_tabs'] ?? [];

        /** @var array{target_word: string, syllables: list<array{glyph: string, order: int}>, completion_audio_script: string} $sequence */
        $sequence = $definition['sequence'];

        /** @var list<array<string, mixed>> $structureCards */
        $structureCards = $definition['structure_cards'] ?? [];

        /** @var array{label: string, path: string, complete_audio: string} $tracing */
        $tracing = $definition['tracing'];

        /** @var array{intro: string, story_audio: string, items: list<array<string, mixed>>} $discovery */
        $discovery = $definition['discovery'];

        /** @var array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string} $demo */
        $demo = $definition['demo'];

        return [
            [
                'station_number' => 1,
                'station_type' => 'variant_matrix',
                'title' => $labels[1] ?? 'تعرّف على العدد 3',
                'instructions' => 'انظر إلى العدد 3 وعدّ التفاحات الثلاث.',
                'sonbol_prompt' => 'هيا نتعرف على العدد ثلاثة مع التفاح!',
                'config' => [
                    'tabs' => array_map(static function (array $tab): array {
                        return [
                            'glyph' => (string) $tab['glyph'],
                            'label' => (string) $tab['label'],
                            'audio_script' => (string) ($tab['audio_script'] ?? ''),
                            'audio_path' => $tab['audio_path'] ?? null,
                            'cards' => array_map(static fn (array $card): array => [
                                'word' => (string) ($card['word'] ?? ''),
                                'emoji' => (string) ($card['emoji'] ?? ''),
                                'highlight' => (string) ($card['highlight'] ?? ''),
                                'audio_script' => (string) ($card['audio_script'] ?? ''),
                                'audio_path' => $card['audio_path'] ?? null,
                                'image_path' => $card['image_path'] ?? null,
                            ], $tab['cards'] ?? []),
                        ];
                    }, $tabs),
                    'voice_targets' => array_values(array_map(
                        static fn (array $tab): string => (string) $tab['glyph'],
                        $tabs,
                    )),
                ],
                'assets' => [
                    'neural_audio' => true,
                ],
            ],
            [
                'station_number' => 2,
                'station_type' => 'sequence_pop',
                'title' => $labels[2] ?? 'عدّ بالترتيب',
                'instructions' => 'افقع الأعداد بالترتيب: ١ ثم ٢ ثم ٣.',
                'sonbol_prompt' => 'هيا نعد معاً: واحد، اثنان، ثلاثة!',
                'config' => [
                    'target_word' => (string) $sequence['target_word'],
                    'syllables' => array_map(static fn (array $syllable): array => [
                        'glyph' => (string) $syllable['glyph'],
                        'order' => (int) $syllable['order'],
                        'audio_path' => 'audio/grade1/lessons/number-3/count-'.$syllable['glyph'].'.mp3',
                    ], $sequence['syllables']),
                    'distractors' => [],
                    'completion_audio_script' => (string) $sequence['completion_audio_script'],
                    'completion_audio_path' => 'audio/grade1/lessons/number-3/count-complete.mp3',
                ],
                'assets' => null,
            ],
            [
                'station_number' => 3,
                'station_type' => 'structure_cards',
                'title' => $labels[3] ?? 'أشكال العدد 3',
                'instructions' => 'اضغط البطاقة لترى طرقاً مختلفة لتمثيل العدد 3.',
                'sonbol_prompt' => 'ثلاثة أصابع، ثلاث نقاط، ثلاث كرات — كلها تعني 3!',
                'config' => [
                    'mode' => 'quantity_representation',
                    'cards' => array_map(static fn (array $card): array => [
                        'id' => (string) $card['id'],
                        'label' => (string) $card['label'],
                        'display_word' => (string) $card['display_word'],
                        'parts' => $card['parts'],
                        'audio_script' => (string) ($card['audio_script'] ?? ''),
                        'audio_path' => 'audio/grade1/lessons/number-3/'.$card['id'].'.mp3',
                    ], $structureCards),
                ],
                'assets' => null,
            ],
            [
                'station_number' => 4,
                'station_type' => 'trace_canvas',
                'title' => $labels[4] ?? 'اكتب العدد 3',
                'instructions' => (string) $tracing['label'],
                'sonbol_prompt' => 'ارسم العدد 3 من الأعلى: قوس ثم قوس!',
                'config' => [
                    'view_box' => '0 0 140 140',
                    'paths' => [
                        [
                            'id' => 'stroke-1',
                            'd' => (string) $tracing['path'],
                            'order' => 1,
                            'direction' => 'top_to_bottom_double_curve',
                        ],
                    ],
                    'checkpoints' => [
                        ['t' => 0.0, 'label' => 'start'],
                        ['t' => 0.45, 'label' => 'mid_waist'],
                        ['t' => 1.0, 'label' => 'end'],
                    ],
                    'tolerance_px' => 18,
                    'complete_audio_script' => (string) $tracing['complete_audio'],
                    'complete_audio_path' => 'audio/grade1/lessons/number-3/trace-complete.mp3',
                ],
                'assets' => null,
            ],
            [
                'station_number' => 5,
                'station_type' => 'scratch_discover',
                'title' => $labels[5] ?? 'اكتشف العنب',
                'instructions' => (string) $discovery['intro'],
                'sonbol_prompt' => (string) $discovery['story_audio'],
                'config' => [
                    'background_image' => 'images/lessons/number-3/orchard-bg.webp',
                    'overlay_image' => 'images/lessons/number-3/sand-overlay.webp',
                    'overlay_mode' => 'sand',
                    'reveal_threshold' => 0.55,
                    'story_audio_script' => (string) $discovery['story_audio'],
                    'story_audio_path' => 'audio/grade1/lessons/number-3/orchard-story.mp3',
                    'hotspots' => array_map(static fn (array $item): array => [
                        'id' => (string) $item['id'],
                        'label' => (string) $item['label'],
                        'emoji' => (string) $item['emoji'],
                        'correct' => (bool) $item['correct'],
                        'audio_script' => (string) ($item['audio_script'] ?? ''),
                        'audio_path' => 'audio/grade1/lessons/number-3/'.$item['id'].'.mp3',
                        'x' => null,
                        'y' => null,
                        'r' => null,
                    ], $discovery['items']),
                ],
                'assets' => [
                    'overlay_mode' => 'sand',
                ],
            ],
            [
                'station_number' => 6,
                'station_type' => 'guided_demo',
                'title' => $labels[6] ?? 'المعلم الصغير',
                'instructions' => 'شاهد كيف يعدّ سنبل ثلاثة أشياء ثم انتقل للاختبار.',
                'sonbol_prompt' => 'هيا نعد معاً كالمعلمين الصغار!',
                'config' => [
                    'question_text' => (string) $demo['question'],
                    'parts' => $demo['parts'],
                    'explain_script' => (string) $demo['explain_audio'],
                    'explain_audio_path' => 'audio/grade1/lessons/number-3/guided-demo.mp3',
                    'linked_question_id' => null,
                    'cta_label' => (string) $demo['cta'],
                    'quiz_learning_material_id' => $material->id,
                ],
                'assets' => null,
            ],
        ];
    }
}
