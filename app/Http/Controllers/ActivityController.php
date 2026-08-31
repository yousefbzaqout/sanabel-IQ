<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        return response()->json([
            'id' => $activity->id,
            'title' => $activity->title,
            'payload' => $activity->payload,
            'xp_reward' => $activity->xp_reward,
            'status' => $activity->status->value,
        ]);
    }
}
