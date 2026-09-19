<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Tenant;
use App\Services\Tenancy\TenantSeatLimitService;
use App\Support\Tenancy\TenantContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function beforeCreate(): void
    {
        $tenantId = TenantContext::id() ?? auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $tenant = Tenant::query()->find($tenantId);

            if ($tenant !== null) {
                $service = app(TenantSeatLimitService::class);

                if (! $service->hasAvailableSeats($tenant)) {
                    $limit = $service->getSeatLimit($tenant);

                    Notification::make()
                        ->title('تم تجاوز الحد الأقصى لمقاعد الطلاب')
                        ->body("وصلت المدرسة إلى الحد الأقصى للمقاعد المتاحة ({$limit} مقعد). يرجى ترقية خطة الاشتراك لإضافة المزيد من الطلاب.")
                        ->danger()
                        ->persistent()
                        ->send();

                    throw ValidationException::withMessages([
                        'name' => "تم استنفاد الحد الأقصى لمقاعد الطلاب المتاحة للمدرسة ({$limit} مقعد).",
                    ]);
                }
            }
        }
    }
}
