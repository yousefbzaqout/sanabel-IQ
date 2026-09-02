<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Observability\SystemHealthService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke(SystemHealthService $healthService): JsonResponse
    {
        $report = $healthService->assess();

        $statusCode = $report['status'] === 'ok' ? 200 : 503;

        return response()->json($report, $statusCode);
    }
}
