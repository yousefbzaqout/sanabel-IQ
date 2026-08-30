<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'parent_material_id' => null,
            'title' => fake()->sentence(4),
            'payload' => ['questions' => []],
            'xp_reward' => 10,
            'status' => ActivityStatus::Draft,
        ];
    }
}
