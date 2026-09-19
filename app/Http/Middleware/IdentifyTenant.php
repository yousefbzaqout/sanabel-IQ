<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $host = strtolower($request->getHost());
        $hostTenant = $this->resolveFromHost($host);
        $hostLooksTenantScoped = $this->isTenantScopedHost($host);

        if ($hostLooksTenantScoped && $hostTenant === null) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        $tenant = $hostTenant ?? $this->resolveFromUser($request);

        if ($tenant !== null && $tenant->status !== Tenant::STATUS_ACTIVE) {
            abort(Response::HTTP_FORBIDDEN, 'Tenant is inactive.');
        }

        if ($tenant !== null) {
            TenantContext::setTenant($tenant);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    private function resolveFromHost(string $host): ?Tenant
    {
        $byDomain = Tenant::query()->where('domain', $host)->first();

        if ($byDomain !== null) {
            return $byDomain;
        }

        if (! $this->isTenantScopedHost($host)) {
            return null;
        }

        $subdomain = explode('.', $host)[0] ?? '';

        if ($subdomain === '' || in_array($subdomain, ['www', 'app', 'api', 'admin'], true)) {
            return null;
        }

        return Tenant::query()->where('slug', $subdomain)->first();
    }

    private function resolveFromUser(Request $request): ?Tenant
    {
        $user = $request->user();

        if ($user === null || $user->tenant_id === null) {
            return null;
        }

        return Tenant::query()->find($user->tenant_id);
    }

    private function isTenantScopedHost(string $host): bool
    {
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (str_ends_with($host, '.localhost')) {
            return false;
        }

        // Single-label hosts (e.g. "sanabel") are treated as central.
        if (! str_contains($host, '.')) {
            return false;
        }

        return true;
    }
}
