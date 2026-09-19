<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;

final class TenantContext
{
    private static ?int $tenantId = null;

    private static ?Tenant $tenant = null;

    public static function setTenant(?Tenant $tenant): void
    {
        self::set($tenant);
    }

    public static function set(?Tenant $tenant): void
    {
        self::$tenant = $tenant;
        self::$tenantId = $tenant?->id;
    }

    public static function setId(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
        self::$tenant = null;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }

    public static function tenant(): ?Tenant
    {
        if (self::$tenant !== null) {
            return self::$tenant;
        }

        if (self::$tenantId === null) {
            return null;
        }

        return self::$tenant = Tenant::query()->find(self::$tenantId);
    }

    public static function check(): bool
    {
        return self::$tenantId !== null;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$tenant = null;
    }
}
