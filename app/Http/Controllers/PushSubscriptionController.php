<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? null,
        );

        return response()->json(['status' => 'subscribed'], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH],
        ]);

        $endpoint = $validated['endpoint'];
        $subscription = PushSubscription::findByEndpoint($endpoint);

        if ($subscription === null || ! $request->user()?->ownsPushSubscription($subscription)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $request->user()->deletePushSubscription($endpoint);

        return response()->noContent();
    }
}
