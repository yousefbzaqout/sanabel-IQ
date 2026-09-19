<?php

declare(strict_types=1);

namespace Tests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TenantContext::clear();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }
}
