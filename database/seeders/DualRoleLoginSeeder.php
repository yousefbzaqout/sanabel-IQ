<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\Student\ChildLoginCredentialService;
use App\Services\TenantOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Public dual-role demo accounts for the family/student login switcher.
 */
class DualRoleLoginSeeder extends Seeder
{
    public const PARENT_EMAIL = 'parent@sanabel.test';

    public const FAMILY_CODE = 'SNBL01';

    public const CHILD_A_PIN = '1234';

    public const CHILD_B_PIN = '5678';

    public const PASSWORD = TenantOnboardingService::DEFAULT_PASSWORD;

    public function run(): void
    {
        $parent = User::query()->updateOrCreate(
            ['email' => self::PARENT_EMAIL],
            [
                'name' => 'ولي أمر سنابل',
                'password' => Hash::make(self::PASSWORD),
                'family_code' => self::FAMILY_CODE,
            ],
        );
        $parent->assignRole(UserRole::Parent);
        $parent->forceFill(['family_code' => self::FAMILY_CODE])->save();

        $ziyad = Student::query()->updateOrCreate(
            [
                'user_id' => $parent->id,
                'name' => 'زياد',
            ],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 0,
                'coins' => 0,
                'lives' => 3,
            ],
        );

        $sara = Student::query()->updateOrCreate(
            [
                'user_id' => $parent->id,
                'name' => 'سارة',
            ],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 40,
                'coins' => 10,
                'lives' => 3,
            ],
        );

        $credentials = app(ChildLoginCredentialService::class);
        $credentials->setPin($ziyad, self::CHILD_A_PIN);
        $credentials->setPin($sara, self::CHILD_B_PIN);
    }
}
