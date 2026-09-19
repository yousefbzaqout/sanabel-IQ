<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantSeatLimitService
{
    public function getStudentCount(Tenant $tenant): int
    {
        return $tenant->studentCount();
    }

    public function getSeatLimit(Tenant $tenant): int
    {
        return $tenant->seatLimit();
    }

    public function hasAvailableSeats(Tenant $tenant, int $additional = 1): bool
    {
        return $tenant->hasAvailableSeats($additional);
    }

    public function getRemainingSeats(Tenant $tenant): int
    {
        return $tenant->remainingSeats();
    }

    /**
     * @return array{
     *     used: int,
     *     limit: int,
     *     remaining: int,
     *     usage_percentage: float,
     *     is_limit_reached: bool
     * }
     */
    public function getSeatMetrics(Tenant $tenant): array
    {
        $used = $this->getStudentCount($tenant);
        $limit = $this->getSeatLimit($tenant);
        $remaining = max(0, $limit - $used);
        $percentage = $limit > 0 ? round(($used / $limit) * 100, 1) : 100.0;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'usage_percentage' => $percentage,
            'is_limit_reached' => $used >= $limit,
        ];
    }

    /**
     * @throws ValidationException
     */
    public function ensureCanAddStudent(Tenant $tenant, int $additional = 1): void
    {
        if (! $this->hasAvailableSeats($tenant, $additional)) {
            $limit = $this->getSeatLimit($tenant);

            throw ValidationException::withMessages([
                'seat_limit' => "تم استنفاد الحد الأقصى لمقاعد الطلاب المتاحة للمدرسة ({$limit} مقعد). يرجى ترقية خطة الاشتراك لإضافة المزيد من الطلاب.",
            ]);
        }
    }
}
