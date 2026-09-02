<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentStreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentStreak>
 */
class StudentStreakFactory extends Factory
{
    protected $model = StudentStreak::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'current_streak' => 0,
            'max_streak' => 0,
            'last_activity_date' => null,
        ];
    }
}
