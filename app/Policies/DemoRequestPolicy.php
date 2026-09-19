<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DemoRequest;
use App\Models\User;

class DemoRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
