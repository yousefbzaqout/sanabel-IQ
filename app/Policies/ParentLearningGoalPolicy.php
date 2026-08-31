<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ParentLearningGoal;
use App\Models\User;

class ParentLearningGoalPolicy
{
    public function delete(User $user, ParentLearningGoal $parentLearningGoal): bool
    {
        return $user->id === $parentLearningGoal->parent_id;
    }
}
