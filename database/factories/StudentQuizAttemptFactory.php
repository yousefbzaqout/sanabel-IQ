<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentQuizAttempt>
 */
class StudentQuizAttemptFactory extends Factory
{
    protected $model = StudentQuizAttempt::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'learning_material_id' => LearningMaterial::factory()->published(),
            'total_questions' => 3,
            'correct_answers' => 2,
            'score_percentage' => 66.67,
            'xp_earned' => 60,
            'completed_at' => now(),
        ];
    }
}
