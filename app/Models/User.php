<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\BelongsToTenant;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['tenant_id', 'name', 'email', 'password', 'family_code'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant;

    use HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === UserRole::TenantAdmin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isParent(): bool
    {
        return $this->role === UserRole::Parent;
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->isTenantAdmin();
    }

    public function assignRole(UserRole $role): void
    {
        $this->role = $role;
        $this->save();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->canAccessAdminPanel(),
            'parent' => $this->isParent(),
            default => false,
        };
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** @return HasOne<Student, $this> */
    public function learningProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'login_user_id');
    }

    /**
     * Students this auth user may operate as (parent household or own learning profile).
     *
     * @return Builder<Student>
     */
    public function accessibleStudentsQuery(): Builder
    {
        if ($this->isStudent()) {
            return Student::query()->where('login_user_id', $this->id);
        }

        return $this->students()->getQuery();
    }

    public function findAccessibleStudentOrFail(int $studentId): Student
    {
        return $this->accessibleStudentsQuery()->whereKey($studentId)->firstOrFail();
    }

    /** @return HasMany<ParentMaterial, $this> */
    public function parentMaterials(): HasMany
    {
        return $this->hasMany(ParentMaterial::class);
    }

    /** @return HasMany<ParentLearningGoal, $this> */
    public function parentLearningGoals(): HasMany
    {
        return $this->hasMany(ParentLearningGoal::class, 'parent_id');
    }
}
