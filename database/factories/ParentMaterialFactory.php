<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentMaterial>
 */
class ParentMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_id' => Student::factory(),
            'title' => fake()->sentence(3),
            'file_path' => 'materials/'.fake()->uuid().'.pdf',
            'type' => MaterialType::Exam,
            'status' => MaterialStatus::Pending,
        ];
    }
}
