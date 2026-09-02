<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(),
            'name_ar' => fake()->words(2, true),
            'description_ar' => fake()->sentence(),
            'icon' => 'star',
            'criteria_type' => 'xp_threshold',
            'criteria_value' => 100,
        ];
    }
}
