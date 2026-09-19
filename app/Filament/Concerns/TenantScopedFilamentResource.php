<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\Tenancy\FilamentTenantSynchronizer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ensures Filament resources respect TenantContext for tenant admins.
 * Route binding loads without the tenant scope so policies can return 403
 * for cross-tenant records instead of a silent 404.
 */
trait TenantScopedFilamentResource
{
    public static function getEloquentQuery(): Builder
    {
        FilamentTenantSynchronizer::syncFromAuth();

        return parent::getEloquentQuery();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        FilamentTenantSynchronizer::syncFromAuth();

        return parent::getEloquentQuery()->withoutGlobalScope('tenant');
    }
}
