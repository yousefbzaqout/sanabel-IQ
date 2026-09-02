<?php

declare(strict_types=1);

namespace App\Services\AI\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final class ActivitySchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'activity',
            description: 'Interactive learning activity generated from uploaded educational material.',
            properties: [
                new StringSchema(
                    name: 'title',
                    description: 'Arabic title for the generated activity quiz or game.',
                ),
                new ArraySchema(
                    name: 'questions',
                    description: 'List of interactive questions tailored to the material and grade level.',
                    items: new ObjectSchema(
                        name: 'question',
                        description: 'A single interactive question with options and explanation.',
                        properties: [
                            new EnumSchema(
                                name: 'type',
                                description: 'Question format.',
                                options: ['multiple_choice', 'true_false', 'fill_in_the_blank'],
                            ),
                            new StringSchema(
                                name: 'question',
                                description: 'Question prompt in Arabic.',
                            ),
                            new ArraySchema(
                                name: 'options',
                                description: 'Answer options for the question.',
                                items: new StringSchema(
                                    name: 'option',
                                    description: 'A single answer option.',
                                ),
                                minItems: 2,
                            ),
                            new NumberSchema(
                                name: 'correct_index',
                                description: 'Zero-based index of the correct option.',
                                minimum: 0,
                            ),
                            new StringSchema(
                                name: 'explanation',
                                description: 'Short explanation of the correct answer in Arabic.',
                            ),
                        ],
                        requiredFields: ['type', 'question', 'options', 'correct_index', 'explanation'],
                    ),
                    minItems: 1,
                ),
                new NumberSchema(
                    name: 'total_xp',
                    description: 'Total experience points awarded for completing the activity.',
                    minimum: 1,
                ),
            ],
            requiredFields: ['title', 'questions', 'total_xp'],
        );
    }
}
