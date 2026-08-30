<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RewardContract;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RewardContract>
 */
class RewardContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'xp_cost' => fake()->numberBetween(10, 100),
            'is_fulfilled' => false,
            'fulfilled_at' => null,
        ];
    }
}
