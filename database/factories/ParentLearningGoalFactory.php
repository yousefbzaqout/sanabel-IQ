<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ParentGoalStatus;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentLearningGoal>
 */
class ParentLearningGoalFactory extends Factory
{
    protected $model = ParentLearningGoal::class;

    public function definition(): array
    {
        return [
            'parent_id' => User::factory(),
            'student_id' => Student::factory(),
            'subject_id' => null,
            'target_activity_count' => 5,
            'target_xp' => 200,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ];
    }
}
