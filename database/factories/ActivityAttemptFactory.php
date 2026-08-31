<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityAttempt>
 */
class ActivityAttemptFactory extends Factory
{
    protected $model = ActivityAttempt::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'student_id' => Student::factory(),
            'score' => 0,
            'total_questions' => 5,
            'xp_earned' => 0,
            'answers_json' => [],
            'completed_at' => now(),
        ];
    }
}
