<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function view(User $user, Activity $activity): bool
    {
        if ($activity->status !== ActivityStatus::Published) {
            return false;
        }

        $activity->loadMissing('student');

        return $activity->student !== null && $user->id === $activity->student->user_id;
    }
}
