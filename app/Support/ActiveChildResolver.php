<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Student;
use App\Models\User;

class ActiveChildResolver
{
    public function resolve(?User $user): ?Student
    {
        if ($user === null) {
            return null;
        }

        if ($user->isStudent()) {
            return $user->learningProfile;
        }

        $activeId = (int) session('active_student_id', 0);

        if ($activeId > 0) {
            $student = $user->students()->find($activeId);

            if ($student !== null) {
                return $student;
            }
        }

        return $user->students()->orderBy('id')->first();
    }
}
