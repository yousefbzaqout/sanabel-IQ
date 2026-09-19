<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Models\User;

/**
 * Syncs TenantContext for Filament admin panel users.
 * Super admins stay unscoped; tenant admins are locked to their school.
 */
final class FilamentTenantSynchronizer
{
    public static function syncFromAuth(?User $user = null): void
    {
        TenantContext::clear();

        $user ??= auth()->user();

        if (! $user instanceof User) {
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        if (! $user->isTenantAdmin()) {
            return;
        }

        if ($user->tenant_id === null) {
            abort(403, 'Tenant admin is missing a tenant assignment.');
        }

        $tenant = Tenant::query()->find($user->tenant_id);

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            abort(403, 'Tenant is inactive.');
        }

        TenantContext::setTenant($tenant);
    }
}
