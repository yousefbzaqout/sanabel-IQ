<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function select(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function export(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }
}
