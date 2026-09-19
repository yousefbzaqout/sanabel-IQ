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
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTenantAdmin()) {
            return (int) ($student->tenant_id ?? $student->user?->tenant_id) === (int) $user->tenant_id;
        }

        return $user->id === $student->user_id || $user->id === $student->login_user_id;
    }

    public function viewMastery(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function create(User $user): bool
    {
        return ! $user->isStudent();
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTenantAdmin()) {
            return (int) ($student->tenant_id ?? $student->user?->tenant_id) === (int) $user->tenant_id;
        }

        return $user->id === $student->user_id;
    }

    public function delete(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTenantAdmin()) {
            return (int) ($student->tenant_id ?? $student->user?->tenant_id) === (int) $user->tenant_id;
        }

        return $user->id === $student->user_id;
    }

    public function select(User $user, Student $student): bool
    {
        if ($user->isStudent()) {
            return $user->id === $student->login_user_id;
        }

        return $user->id === $student->user_id;
    }

    public function export(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }
}
