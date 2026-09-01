<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentQuizAnswer;
use App\Models\StudentQuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentQuizAnswer>
 */
class StudentQuizAnswerFactory extends Factory
{
    protected $model = StudentQuizAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => StudentQuizAttempt::factory(),
            'question_id' => Question::factory(),
            'selected_option_id' => QuestionOption::factory(),
            'is_correct' => false,
            'points_awarded' => 0,
        ];
    }
}
