<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $gradeLevel = fake()->numberBetween(1, 5);
        $code = strtoupper(Str::slug($name, '-')).'-G'.$gradeLevel;

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$gradeLevel.'-'.Str::random(4),
            'code' => $code,
            'grade_level' => $gradeLevel,
            'icon' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
