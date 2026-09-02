<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ParentLearningGoal;
use App\Models\User;

class ParentLearningGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ParentLearningGoal $parentLearningGoal): bool
    {
        return $user->id === $parentLearningGoal->parent_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ParentLearningGoal $parentLearningGoal): bool
    {
        return $user->id === $parentLearningGoal->parent_id;
    }

    public function delete(User $user, ParentLearningGoal $parentLearningGoal): bool
    {
        return $user->id === $parentLearningGoal->parent_id;
    }
}
