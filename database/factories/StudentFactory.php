<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'grade_level' => fake()->numberBetween(1, 6),
            'school_term' => fake()->randomElement([1, 2]),
            'total_xp' => 0,
            'coins' => 0,
            'lives' => 3,
            'avatar_path' => null,
        ];
    }
}
