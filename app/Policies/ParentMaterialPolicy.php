<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ParentMaterial;
use App\Models\User;

class ParentMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ParentMaterial $parentMaterial): bool
    {
        return $user->id === $parentMaterial->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, ParentMaterial $parentMaterial): bool
    {
        return $user->id === $parentMaterial->user_id;
    }
}
