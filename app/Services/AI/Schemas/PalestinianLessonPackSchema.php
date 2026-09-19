<?php

declare(strict_types=1);

namespace App\Services\AI\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

/**
 * Prism structured schema aligned with {@see \App\Support\Curriculum\PalestinianLessonSchema}.
 */
final class PalestinianLessonPackSchema
{
    public static function make(): ObjectSchema
    {
        $card = new ObjectSchema(
            name: 'card',
            description: 'Phoneme / number example card.',
            properties: [
                new StringSchema('word', 'Word or numeral label in Arabic.'),
                new StringSchema('emoji', 'Emoji visual cue.'),
                new StringSchema('audio', 'Short TTS script.'),
                new StringSchema('highlight', 'Highlighted glyph substring.'),
            ],
            requiredFields: ['word', 'emoji', 'audio', 'highlight'],
        );

        $tab = new ObjectSchema(
            name: 'tab',
            description: 'A phoneme or number voice tab.',
            properties: [
                new StringSchema('glyph', 'Glyph shown on the tab (letter with haraka or digit).'),
                new StringSchema('label', 'Arabic label.'),
                new StringSchema('audio_script', 'Narration script for the tab.'),
                new ArraySchema('cards', 'Example cards for the tab.', $card, minItems: 1),
            ],
            requiredFields: ['glyph', 'label', 'audio_script', 'cards'],
        );

        $option = new ObjectSchema(
            name: 'option',
            description: 'Quiz option.',
            properties: [
                new StringSchema('text', 'Option text in Arabic.'),
                new NumberSchema('correct', '1 if correct, 0 otherwise.', minimum: 0, maximum: 1),
            ],
            requiredFields: ['text', 'correct'],
        );

        $visualItems = new ObjectSchema(
            name: 'visual_items',
            description: 'Optional visual counting payload.',
            properties: [
                new StringSchema('emoji', 'Repeated emoji for counting.'),
                new NumberSchema('count', 'How many items to show.', minimum: 1),
            ],
            requiredFields: ['emoji', 'count'],
        );

        $comparison = new ObjectSchema(
            name: 'comparison',
            description: 'Optional comparison payload.',
            properties: [
                new NumberSchema('left', 'Left operand.', minimum: 0),
                new NumberSchema('right', 'Right operand.', minimum: 0),
                new EnumSchema('operator', 'Comparison operator.', options: ['gt', 'lt', 'eq']),
            ],
            requiredFields: ['left', 'right', 'operator'],
        );

        $question = new ObjectSchema(
            name: 'question',
            description: 'A quiz question.',
            properties: [
                new EnumSchema('type', 'Question type.', options: ['mcq', 'true_false']),
                new StringSchema('prompt', 'Question prompt in Arabic.'),
                new StringSchema('explanation', 'Short explanation in Arabic.'),
                new StringSchema('mascot_hint', 'Sanbal hint in Arabic.'),
                new ArraySchema('options', 'Answer options.', $option, minItems: 2),
                $visualItems,
                $comparison,
            ],
            requiredFields: ['type', 'prompt', 'explanation', 'options'],
        );

        $stroke = new ObjectSchema(
            name: 'stroke',
            description: 'Finger-trace stroke guide.',
            properties: [
                new StringSchema('label', 'Trace instruction in Arabic.'),
                new StringSchema('path', 'SVG path d attribute for the glyph.'),
                new StringSchema('direction', 'Stroke direction hint.'),
                new StringSchema('complete_audio', 'Completion praise script.'),
            ],
            requiredFields: ['label', 'path', 'direction', 'complete_audio'],
        );

        $phonemes = new ObjectSchema(
            name: 'phonemes',
            description: 'Voice targets and tabs.',
            properties: [
                new ArraySchema(
                    name: 'voice_targets',
                    description: 'List of glyphs to pronounce.',
                    items: new StringSchema('voice_target', 'A single glyph.'),
                    minItems: 1,
                ),
                new ArraySchema('tabs', 'Interactive voice tabs.', $tab, minItems: 1),
            ],
            requiredFields: ['voice_targets', 'tabs'],
        );

        $quiz = new ObjectSchema(
            name: 'quiz',
            description: 'Lesson quiz.',
            properties: [
                new ArraySchema('questions', 'Quiz questions.', $question, minItems: 1),
            ],
            requiredFields: ['questions'],
        );

        return new ObjectSchema(
            name: 'palestinian_lesson_pack',
            description: 'Sanabel-IQ Palestinian curriculum interactive lesson pack.',
            properties: [
                new StringSchema('schema_version', 'Pack schema version, e.g. 1.0.0.'),
                new NumberSchema('grade_level', 'Grade 1-6.', minimum: 1, maximum: 6),
                new NumberSchema('semester', 'Semester 1 or 2.', minimum: 1, maximum: 2),
                new EnumSchema('subject_code', 'Subject code.', options: ['AR', 'MATH', 'SCI']),
                new EnumSchema('content_kind', 'letter or number.', options: ['letter', 'number']),
                new StringSchema('lesson_key', 'Stable lesson key, e.g. ar-g1-math-ai-numbers-1.'),
                new StringSchema('audio_slug', 'Audio directory slug.'),
                new StringSchema('key', 'Short file key slug.'),
                new StringSchema('title', 'Lesson title in Arabic.'),
                new StringSchema('subtitle', 'Lesson subtitle in Arabic.'),
                new StringSchema('material_title', 'Unique learning material title.'),
                new StringSchema('letter', 'Primary Arabic letter when content_kind=letter.'),
                new StringSchema('digit', 'Primary Eastern Arabic digit when content_kind=number.'),
                new ArraySchema(
                    name: 'digits',
                    description: 'Digits covered by the lesson.',
                    items: new StringSchema('digit_item', 'A digit glyph.'),
                ),
                new NumberSchema('order_column', 'Ordering within subject.', minimum: 1),
                new NumberSchema('xp_reward', 'XP reward.', minimum: 1),
                new StringSchema('description', 'Short Arabic description.'),
                $phonemes,
                $stroke,
                $quiz,
                new ObjectSchema(
                    name: 'mascot_hints',
                    description: 'Sanbal station prompts.',
                    properties: [
                        new ObjectSchema(
                            name: 'station_prompts',
                            description: 'Prompts keyed by station number as strings 1-6.',
                            properties: [
                                new StringSchema('1', 'Station 1 prompt.'),
                                new StringSchema('2', 'Station 2 prompt.'),
                                new StringSchema('3', 'Station 3 prompt.'),
                                new StringSchema('4', 'Station 4 prompt.'),
                                new StringSchema('5', 'Station 5 prompt.'),
                                new StringSchema('6', 'Station 6 prompt.'),
                            ],
                            requiredFields: ['1', '2', '3', '4', '5', '6'],
                        ),
                    ],
                    requiredFields: ['station_prompts'],
                ),
            ],
            requiredFields: [
                'schema_version',
                'grade_level',
                'semester',
                'subject_code',
                'content_kind',
                'lesson_key',
                'audio_slug',
                'key',
                'title',
                'subtitle',
                'material_title',
                'order_column',
                'xp_reward',
                'description',
                'phonemes',
                'stroke',
                'quiz',
                'mascot_hints',
            ],
        );
    }
}
