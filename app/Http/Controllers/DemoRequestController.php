<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreDemoRequestRequest;
use App\Models\DemoRequest;
use App\Models\User;
use App\Notifications\DemoRequestSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class DemoRequestController extends Controller
{
    public function store(StoreDemoRequestRequest $request): JsonResponse
    {
        $demoRequest = DemoRequest::query()->create([
            ...$request->validated(),
            'status' => 'new',
        ]);

        $superAdmins = User::query()
            ->withoutGlobalScopes()
            ->where('role', UserRole::Admin)
            ->get();

        if ($superAdmins->isNotEmpty()) {
            Notification::send($superAdmins, new DemoRequestSubmittedNotification($demoRequest));
        }

        return response()->json([
            'message' => 'تم استلام طلب العرض التجريبي بنجاح. سيتواصل معكم فريق سنابل IQ قريباً.',
            'data' => [
                'id' => $demoRequest->id,
            ],
        ], 201);
    }
}
