<?php

declare(strict_types=1);

namespace App\Services\AI\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final class StudyRecommendationSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'study_recommendation',
            description: 'Personalized Arabic study recommendations for a parent supporting their child.',
            properties: [
                new StringSchema(
                    name: 'focus_area',
                    description: 'Primary focus area for the parent to emphasize with the child, in Arabic.',
                ),
                new ArraySchema(
                    name: 'parent_tips',
                    description: 'Actionable tips the parent can apply at home, in Arabic.',
                    items: new StringSchema(
                        name: 'tip',
                        description: 'A single actionable study tip for the parent.',
                    ),
                    minItems: 1,
                ),
            ],
            requiredFields: ['focus_area', 'parent_tips'],
        );
    }
}
