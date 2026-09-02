<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Observability\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_200_and_ok_status_when_all_services_are_up(): void
    {
        $this->partialMock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkRedis')->andReturn('up');
        });

        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'services' => [
                    'database' => 'up',
                    'redis' => 'up',
                    'storage' => 'up',
                ],
            ]);
    }

    public function test_health_check_returns_503_when_database_or_redis_connection_fails(): void
    {
        $this->partialMock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDatabase')->andReturn('down');
            $mock->shouldReceive('checkRedis')->andReturn('up');
            $mock->shouldReceive('checkStorage')->andReturn('up');
        });

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'degraded',
                'services' => [
                    'database' => 'down',
                    'redis' => 'up',
                    'storage' => 'up',
                ],
            ]);
    }
}
