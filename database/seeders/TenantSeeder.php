<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public const DEFAULT_SLUG = 'sanabel-model-school';

    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['slug' => self::DEFAULT_SLUG],
            [
                'name' => 'مدرسة سنابل النموذجية',
                'domain' => 'demo.sanabel.test',
                'status' => Tenant::STATUS_ACTIVE,
            ],
        );
    }
}
