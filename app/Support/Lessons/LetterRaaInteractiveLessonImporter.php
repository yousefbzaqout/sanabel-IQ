<?php

declare(strict_types=1);

namespace App\Support\Lessons;

use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use RuntimeException;

/**
 * Maps {@see LetterRaaLessonDefinition} into interactive_lessons + stations (idempotent).
 */
final class LetterRaaInteractiveLessonImporter
{
    public const LESSON_KEY = 'ar-g1-letter-raa';

    public function import(): InteractiveLesson
    {
        $definition = LetterRaaLessonDefinition::definition();

        $material = LearningMaterial::query()
            ->where('title', LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE)
            ->first();

        if ($material === null) {
            throw new RuntimeException(
                'Missing LearningMaterial for Letter Raa: '.LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
            );
        }

        $lesson = InteractiveLesson::query()->updateOrCreate(
            ['lesson_key' => self::LESSON_KEY],
            [
                'learning_material_id' => $material->id,
                'title' => (string) $definition['title'],
                'subtitle' => (string) $definition['subtitle'],
                'subject_code' => 'AR',
                'grade_level' => 1,
                'station_count' => 6,
                'status' => 'published',
                'intro_audio_path' => null,
                'meta' => [
                    'legacy_key' => LetterRaaLessonDefinition::KEY,
                    'source' => LetterRaaLessonDefinition::class,
                    'quiz_material_title' => LetterRaaLessonDefinition::QUIZ_MATERIAL_TITLE,
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

        /** @var list<array{glyph: string, label: string, cards: list<array<string, mixed>>}> $tabs */
        $tabs = $definition['diacritic_tabs'] ?? [];

        /** @var list<array{label: string, word: string, parts: list<array{text: string, highlight: bool}>, audio: string}> $positions */
        $positions = $definition['positions'] ?? [];

        /** @var array{label: string, path: string, complete_audio: string} $tracing */
        $tracing = $definition['tracing'] ?? ['label' => '', 'path' => '', 'complete_audio' => ''];

        /** @var array{intro: string, items: list<array{id: string, label: string, emoji: string, correct: bool, audio: string}>} $discovery */
        $discovery = $definition['discovery'] ?? ['intro' => '', 'items' => []];

        /** @var array{question: string, parts: list<array{text: string, highlight: bool}>, explain_audio: string, cta: string} $demo */
        $demo = $definition['demo'] ?? ['question' => '', 'parts' => [], 'explain_audio' => '', 'cta' => ''];

        $positionIds = ['begin', 'middle', 'end'];

        return [
            [
                'station_number' => 1,
                'station_type' => 'variant_matrix',
                'title' => $labels[1] ?? 'الحركات الثلاث',
                'instructions' => 'استمع للحركات الثلاث واختر الكلمة المناسبة.',
                'sonbol_prompt' => 'هيا نسمع رَ، رُ، رِ معاً!',
                'config' => [
                    'tabs' => array_map(
                        static fn (array $tab): array => [
                            'glyph' => $tab['glyph'],
                            'label' => $tab['label'],
                            'audio_script' => $tab['glyph'],
                            'cards' => array_map(
                                static fn (array $card): array => [
                                    'word' => $card['word'],
                                    'emoji' => $card['emoji'],
                                    'highlight' => $card['highlight'],
                                    'audio_script' => $card['audio'],
                                    'audio_path' => null,
                                    'image_path' => null,
                                ],
                                $tab['cards'],
                            ),
                        ],
                        $tabs,
                    ),
                    'voice_targets' => array_values(array_map(
                        static fn (array $tab): string => $tab['glyph'],
                        $tabs,
                    )),
                ],
                'assets' => null,
            ],
            [
                'station_number' => 2,
                'station_type' => 'sequence_pop',
                'title' => $labels[2] ?? 'فرقعة الفقاعات',
                'instructions' => 'افقع المقاطع بالترتيب لتكوين الكلمة.',
                'sonbol_prompt' => 'هيا نبني كلمة رَمَل!',
                'config' => [
                    'target_word' => 'رَمَل',
                    'syllables' => [
                        ['glyph' => 'رَ', 'order' => 1, 'audio_path' => null],
                        ['glyph' => 'مَ', 'order' => 2, 'audio_path' => null],
                        ['glyph' => 'لْ', 'order' => 3, 'audio_path' => null],
                    ],
                    'distractors' => [],
                    'completion_audio_script' => 'أحسنت! كوّنت كلمة رَمَل',
                    'completion_audio_path' => null,
                ],
                'assets' => null,
            ],
            [
                'station_number' => 3,
                'station_type' => 'structure_cards',
                'title' => $labels[3] ?? 'مواقع الحرف',
                'instructions' => 'اضغط البطاقة لترى أين يختبئ حرف الراء.',
                'sonbol_prompt' => 'أين يختبئ حرف الراء؟ في البداية، الوسط، أو النهاية؟',
                'config' => [
                    'mode' => 'letter_position',
                    'cards' => array_values(array_map(
                        static function (array $position, int $index) use ($positionIds): array {
                            return [
                                'id' => $positionIds[$index] ?? 'position-'.($index + 1),
                                'label' => $position['label'],
                                'display_word' => $position['word'],
                                'parts' => $position['parts'],
                                'audio_script' => $position['audio'],
                                'audio_path' => null,
                            ];
                        },
                        $positions,
                        array_keys($positions),
                    )),
                ],
                'assets' => null,
            ],
            [
                'station_number' => 4,
                'station_type' => 'trace_canvas',
                'title' => $labels[4] ?? 'التتبع بالإصبع',
                'instructions' => (string) $tracing['label'],
                'sonbol_prompt' => 'ارسم حرف الراء بإصبعك من الأعلى إلى الأسفل.',
                'config' => [
                    'view_box' => '0 0 140 140',
                    'paths' => [
                        [
                            'id' => 'stroke-1',
                            'd' => (string) $tracing['path'],
                            'order' => 1,
                            'direction' => 'top_to_bottom_arc',
                        ],
                    ],
                    'checkpoints' => [
                        ['t' => 0.0, 'label' => 'start'],
                        ['t' => 0.5, 'label' => 'mid'],
                        ['t' => 1.0, 'label' => 'end'],
                    ],
                    'tolerance_px' => 18,
                    'complete_audio_script' => (string) $tracing['complete_audio'],
                    'complete_audio_path' => null,
                ],
                'assets' => null,
            ],
            [
                'station_number' => 5,
                'station_type' => 'scratch_discover',
                'title' => $labels[5] ?? 'الاستكشاف البيئي',
                'instructions' => (string) $discovery['intro'],
                'sonbol_prompt' => 'مرحباً! هذه بيارة الرمان. انظر… رُمّان تبدأ بحرف الراء!',
                'config' => [
                    'background_image' => null,
                    'overlay_image' => null,
                    'overlay_mode' => 'sand',
                    'reveal_threshold' => 0.55,
                    'story_audio_script' => 'مرحباً! هذه بيارة الرمان. انظر… رُمّان تبدأ بحرف الراء!',
                    'story_audio_path' => null,
                    'hotspots' => array_map(
                        static fn (array $item): array => [
                            'id' => $item['id'],
                            'label' => $item['label'],
                            'emoji' => $item['emoji'],
                            'correct' => (bool) $item['correct'],
                            'audio_script' => $item['audio'],
                            'audio_path' => null,
                            'x' => null,
                            'y' => null,
                            'r' => null,
                        ],
                        $discovery['items'],
                    ),
                ],
                'assets' => [
                    'overlay_mode' => 'sand',
                ],
            ],
            [
                'station_number' => 6,
                'station_type' => 'guided_demo',
                'title' => $labels[6] ?? 'المعلم الصغير',
                'instructions' => 'شاهد كيف يشرح سنبل السؤال ثم انتقل للاختبار.',
                'sonbol_prompt' => 'هيا نشرح السؤال معاً كالمعلمين الصغار!',
                'config' => [
                    'question_text' => (string) $demo['question'],
                    'parts' => $demo['parts'],
                    'explain_script' => (string) $demo['explain_audio'],
                    'explain_audio_path' => null,
                    'linked_question_id' => null,
                    'cta_label' => (string) $demo['cta'],
                    'quiz_learning_material_id' => $material->id,
                ],
                'assets' => null,
            ],
        ];
    }
}
