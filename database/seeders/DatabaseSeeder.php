<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Demo accounts + analytics: `php artisan db:seed --class=DemoPresentationSeeder`
     * Parent dashboard marketing fill: `php artisan db:seed --class=ParentDashboardMarketingSeeder`
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            DemoPresentationSeeder::class,
            B2bDemoTenantSeeder::class,
            DualRoleLoginSeeder::class,
            ParentDashboardMarketingSeeder::class,
        ]);
    }
}
