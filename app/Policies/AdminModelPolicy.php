<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AdminModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allowsTenantAccess($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allowsTenantAccess($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allowsTenantAccess($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function reorder(User $user): bool
    {
        return $user->canAccessAdminPanel();
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allowsTenantAccess($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allowsTenantAccess($user, $model);
    }

    private function allowsTenantAccess(User $user, Model $model): bool
    {
        if (! $user->canAccessAdminPanel()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($model instanceof \App\Models\Tenant) {
            return (int) $model->getKey() === (int) $user->tenant_id;
        }

        if (! $this->modelUsesTenant($model)) {
            return true;
        }

        return (int) $model->getAttribute('tenant_id') === (int) $user->tenant_id;
    }

    private function modelUsesTenant(Model $model): bool
    {
        return in_array(BelongsToTenant::class, class_uses_recursive($model), true);
    }
}
