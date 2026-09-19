<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ChildLoginCredentialService
{
    public const FAMILY_CODE_LENGTH = 6;

    public const PIN_PATTERN = '/^\d{4}$/';

    public function ensureFamilyCode(User $parent): string
    {
        if (! $parent->isParent()) {
            throw new InvalidArgumentException('Family codes are only issued to parent accounts.');
        }

        $parent->refresh();

        if (is_string($parent->family_code) && $parent->family_code !== '') {
            return strtoupper($parent->family_code);
        }

        $code = $this->generateUniqueFamilyCode();
        $parent->forceFill(['family_code' => $code])->save();

        return $code;
    }

    public function setPin(Student $child, string $pin): User
    {
        if (! preg_match(self::PIN_PATTERN, $pin)) {
            throw new InvalidArgumentException('PIN must be exactly 4 digits.');
        }

        return DB::transaction(function () use ($child, $pin): User {
            $child->loadMissing('user');
            $parent = $child->user;
            if ($parent === null || ! $parent->isParent()) {
                throw new InvalidArgumentException('Child must belong to a parent household.');
            }

            $this->ensureFamilyCode($parent);

            $loginUser = $child->loginUser;
            $email = $this->syntheticEmail($child);

            if ($loginUser === null) {
                $loginUser = User::query()->create([
                    'name' => $child->name,
                    'email' => $email,
                    'password' => $pin,
                    'tenant_id' => $parent->tenant_id,
                ]);
                $loginUser->assignRole(UserRole::Student);
            } else {
                $loginUser->forceFill([
                    'name' => $child->name,
                    'email' => $email,
                    'password' => $pin,
                    'tenant_id' => $parent->tenant_id,
                ])->save();
                if (! $loginUser->isStudent()) {
                    $loginUser->assignRole(UserRole::Student);
                }
            }

            $child->forceFill([
                'pin_hash' => Hash::make($pin),
                'login_enabled_at' => now(),
                'login_user_id' => $loginUser->id,
            ])->save();

            return $loginUser->fresh() ?? $loginUser;
        });
    }

    public function clearPin(Student $child): void
    {
        DB::transaction(function () use ($child): void {
            $loginUser = $child->loginUser;

            $child->forceFill([
                'pin_hash' => null,
                'login_enabled_at' => null,
                'login_user_id' => null,
            ])->save();

            if ($loginUser !== null) {
                $loginUser->delete();
            }
        });
    }

    public function verifyPin(Student $child, string $pin): bool
    {
        if ($child->pin_hash === null || $child->login_user_id === null || $child->login_enabled_at === null) {
            return false;
        }

        return Hash::check($pin, $child->pin_hash);
    }

    /**
     * @return list<array{id: int, name: string, avatar_path: string|null, grade_level: int, login_enabled: bool}>
     */
    public function childrenForFamilyCode(string $familyCode): array
    {
        $code = strtoupper(trim($familyCode));

        $parent = User::query()
            ->where('family_code', $code)
            ->where('role', UserRole::Parent)
            ->first();

        if ($parent === null) {
            return [];
        }

        return $parent->students()
            ->orderBy('name')
            ->get()
            ->map(static fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'avatar_path' => $student->avatar_path,
                'grade_level' => (int) $student->grade_level,
                'login_enabled' => $student->login_enabled_at !== null && $student->pin_hash !== null,
            ])
            ->values()
            ->all();
    }

    public function findChildForLogin(string $familyCode, int $studentId): ?Student
    {
        $code = strtoupper(trim($familyCode));

        $parent = User::query()
            ->where('family_code', $code)
            ->where('role', UserRole::Parent)
            ->first();

        if ($parent === null) {
            return null;
        }

        return $parent->students()
            ->whereKey($studentId)
            ->whereNotNull('pin_hash')
            ->whereNotNull('login_user_id')
            ->whereNotNull('login_enabled_at')
            ->first();
    }

    private function generateUniqueFamilyCode(): string
    {
        do {
            $code = strtoupper(Str::random(self::FAMILY_CODE_LENGTH));
            $code = preg_replace('/[^A-Z0-9]/', 'A', $code) ?? $code;
            $code = substr(str_pad($code, self::FAMILY_CODE_LENGTH, 'X'), 0, self::FAMILY_CODE_LENGTH);
        } while (User::query()->where('family_code', $code)->exists());

        return $code;
    }

    private function syntheticEmail(Student $child): string
    {
        return 'child-'.$child->id.'@students.sanabel.local';
    }
}
