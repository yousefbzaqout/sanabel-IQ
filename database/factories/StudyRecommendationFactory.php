<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudyRecommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyRecommendation>
 */
class StudyRecommendationFactory extends Factory
{
    protected $model = StudyRecommendation::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'weak_topics_json' => [
                [
                    'subject' => 'Science',
                    'accuracy_percent' => 40,
                ],
            ],
            'actionable_tips_json' => [
                'مراجعة الاختبار السابق لمدة 10 دقائق يومياً',
            ],
            'suggested_focus_area' => 'تركيز على مهارات العلوم',
            'generated_at' => now(),
        ];
    }
}
