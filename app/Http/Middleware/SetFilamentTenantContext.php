<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\FilamentTenantSynchronizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets TenantContext for Filament admin panel users.
 * Super admins remain unscoped (cross-tenant). Tenant admins are locked to their school.
 */
class SetFilamentTenantContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        FilamentTenantSynchronizer::syncFromAuth($request->user());

        $tenant = \App\Support\Tenancy\TenantContext::tenant();

        if ($tenant !== null) {
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }
}
