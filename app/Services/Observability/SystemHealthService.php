<?php

declare(strict_types=1);

namespace App\Services\Observability;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SystemHealthService
{
    /**
     * @return array{
     *     status: 'ok'|'degraded',
     *     services: array{
     *         database: 'up'|'down',
     *         redis: 'up'|'down',
     *         storage: 'up'|'down'
     *     }
     * }
     */
    public function assess(): array
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $status = collect($services)->every(static fn (string $state): bool => $state === 'up')
            ? 'ok'
            : 'degraded';

        return [
            'status' => $status,
            'services' => $services,
        ];
    }

    public function checkDatabase(): string
    {
        try {
            DB::connection()->select('select 1 as ping');

            return 'up';
        } catch (Throwable) {
            return 'down';
        }
    }

    public function checkRedis(): string
    {
        try {
            $ping = Redis::connection()->ping();

            if (is_string($ping) && strtoupper($ping) !== 'PONG') {
                return 'down';
            }

            if (config('queue.default') === 'redis') {
                $queueConnection = config('queue.connections.redis.connection', 'default');
                $queuePing = Redis::connection($queueConnection)->ping();

                if (is_string($queuePing) && strtoupper($queuePing) !== 'PONG') {
                    return 'down';
                }
            }

            return 'up';
        } catch (Throwable) {
            return 'down';
        }
    }

    public function checkStorage(): string
    {
        try {
            $storagePath = storage_path();

            $totalBytes = disk_total_space($storagePath);
            $freeBytes = disk_free_space($storagePath);

            if ($totalBytes === false || $freeBytes === false || $totalBytes <= 0) {
                return 'down';
            }

            $freePercent = ($freeBytes / $totalBytes) * 100;

            return $freePercent > 10 ? 'up' : 'down';
        } catch (Throwable) {
            return 'down';
        }
    }
}
