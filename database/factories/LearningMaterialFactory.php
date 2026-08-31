<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningMaterial;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningMaterial>
 */
class LearningMaterialFactory extends Factory
{
    protected $model = LearningMaterial::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'xp_reward' => fake()->numberBetween(10, 500),
            'order_column' => 1,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
        ]);
    }
}
