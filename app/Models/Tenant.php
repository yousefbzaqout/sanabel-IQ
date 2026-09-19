<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'domain',
    'logo_path',
    'primary_color',
    'contact_email',
    'contact_phone',
    'subscription_plan',
    'seat_limit',
    'settings',
    'status',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seat_limit' => 'integer',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** @return HasMany<InteractiveLesson, $this> */
    public function interactiveLessons(): HasMany
    {
        return $this->hasMany(InteractiveLesson::class);
    }

    /** @return HasMany<LessonAnalytic, $this> */
    public function lessonAnalytics(): HasMany
    {
        return $this->hasMany(LessonAnalytic::class);
    }

    /** @return HasMany<TeacherLessonAssignment, $this> */
    public function teacherLessonAssignments(): HasMany
    {
        return $this->hasMany(TeacherLessonAssignment::class);
    }

    public function seatLimit(): int
    {
        return max(0, (int) ($this->seat_limit ?? 50));
    }

    public function studentCount(): int
    {
        return Student::query()
            ->withoutGlobalScope('tenant')
            ->where(function ($query): void {
                $query->where('tenant_id', $this->id)
                    ->orWhereHas('user', function ($q): void {
                        $q->withoutGlobalScope('tenant')->where('tenant_id', $this->id);
                    });
            })
            ->count();
    }

    public function hasAvailableSeats(int $additional = 1): bool
    {
        return ($this->studentCount() + $additional) <= $this->seatLimit();
    }

    public function remainingSeats(): int
    {
        return max(0, $this->seatLimit() - $this->studentCount());
    }
}
