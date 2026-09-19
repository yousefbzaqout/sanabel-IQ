<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DemoRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'school_name',
    'contact_name',
    'job_title',
    'phone',
    'email',
    'seat_range',
    'notes',
    'status',
    'admin_notes',
    'tenant_id',
])]
class DemoRequest extends Model
{
    public const SEAT_RANGES = [
        '50-100',
        '101-300',
        '300+',
    ];

    /**
     * @return array<string, string>
     */
    public static function seatRangeOptions(): array
    {
        return [
            '50-100' => '50 – 100 مقعد',
            '101-300' => '101 – 300 مقعد',
            '300+' => 'أكثر من 300 مقعد',
        ];
    }

    public function recommendedSeatLimit(): int
    {
        return match ($this->seat_range) {
            '50-100' => 100,
            '101-300' => 300,
            '300+' => 1000,
            default => 50,
        };
    }

    public function recommendedSubscriptionPlan(): string
    {
        return match ($this->seat_range) {
            '50-100' => 'standard',
            '101-300' => 'growth',
            '300+' => 'enterprise',
            default => 'standard',
        };
    }

    public function suggestedSlug(): string
    {
        $fromName = Str::slug(Str::ascii($this->school_name));

        if ($fromName !== '') {
            return Str::limit($fromName, 48, '');
        }

        $fromEmail = Str::slug(Str::before($this->email, '@'));

        if ($fromEmail !== '') {
            return Str::limit($fromEmail, 48, '');
        }

        return 'school-'.Str::lower(Str::random(8));
    }

    public function suggestedDomain(): string
    {
        return $this->suggestedSlug().'.sanabel.test';
    }

    public function isConvertible(): bool
    {
        return $this->status !== DemoRequestStatus::Converted
            && $this->tenant_id === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DemoRequestStatus::class,
            'tenant_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
