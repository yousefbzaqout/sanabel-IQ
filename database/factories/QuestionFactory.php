<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\LearningMaterial;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'learning_material_id' => LearningMaterial::factory(),
            'type' => QuestionType::Mcq,
            'prompt' => fake()->sentence(),
            'explanation' => fake()->optional()->sentence(),
            'points' => 10,
            'order_column' => 0,
        ];
    }

    public function mcq(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Mcq,
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::TrueFalse,
        ]);
    }

    public function fillBlank(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::FillBlank,
        ]);
    }
}
