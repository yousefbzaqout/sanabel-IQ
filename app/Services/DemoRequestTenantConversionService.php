<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DemoRequestStatus;
use App\Models\DemoRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantDemoWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DemoRequestTenantConversionService
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     domain?: string|null,
     *     admin_name: string,
     *     admin_email: string,
     *     seat_limit: int,
     *     password?: string,
     *     subscription_plan?: string
     * }  $data
     */
    public function convert(DemoRequest $demoRequest, array $data): Tenant
    {
        if (! $demoRequest->isConvertible()) {
            throw new InvalidArgumentException('لا يمكن تحويل طلب عرض تم تحويله مسبقاً.');
        }

        $password = (string) ($data['password'] ?? Str::password(12));

        return DB::transaction(function () use ($demoRequest, $data, $password): Tenant {
            $tenant = $this->onboarding->provision([
                'name' => (string) $data['name'],
                'slug' => (string) $data['slug'],
                'domain' => $data['domain'] ?? null,
                'admin_name' => (string) $data['admin_name'],
                'admin_email' => (string) $data['admin_email'],
                'password' => $password,
                'seat_limit' => (int) $data['seat_limit'],
                'subscription_plan' => (string) ($data['subscription_plan'] ?? $demoRequest->recommendedSubscriptionPlan()),
                'contact_email' => (string) $data['admin_email'],
                'contact_phone' => $demoRequest->phone,
            ]);

            $demoRequest->forceFill([
                'status' => DemoRequestStatus::Converted,
                'tenant_id' => $tenant->id,
                'admin_notes' => trim(
                    ((string) ($demoRequest->admin_notes ?? ''))."\n".
                    'تم التحويل إلى مدرسة تجريبية (tenant #'.$tenant->id.') في '.now()->toDateTimeString()
                ),
            ])->save();

            $admin = User::query()
                ->withoutTenantScope()
                ->where('email', (string) $data['admin_email'])
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();

            $admin->notify(new TenantDemoWelcomeNotification(
                tenant: $tenant,
                temporaryPassword: $password,
                loginUrl: url('/login'),
            ));

            return $tenant;
        });
    }
}
