<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\TenantOnboardingService;
use Illuminate\Database\Seeder;

class B2bDemoTenantSeeder extends Seeder
{
    public const NAME = 'مدرسة الأمل النموذجية';

    public const SLUG = 'alamal-model-school';

    public const DOMAIN = 'alamal.sanabel.test';

    public const ADMIN_EMAIL = 'admin@alamal.sanabel.test';

    public const TEACHER_EMAIL = 'teacher@alamal.sanabel.test';

    public const STUDENT_EMAIL = 'parent@alamal.sanabel.test';

    public const STUDENT_DISPLAY_NAME = 'سارة الأمل';

    public const PASSWORD = TenantOnboardingService::DEFAULT_PASSWORD;

    public function run(): void
    {
        app(TenantOnboardingService::class)->onboard([
            'name' => self::NAME,
            'slug' => self::SLUG,
            'domain' => self::DOMAIN,
            'admin_email' => self::ADMIN_EMAIL,
            'admin_name' => 'مدير الأمل',
            'teacher_email' => self::TEACHER_EMAIL,
            'teacher_name' => 'معلم الأمل',
            'student_email' => self::STUDENT_EMAIL,
            'student_name' => 'ولي أمر الأمل',
            'student_display_name' => self::STUDENT_DISPLAY_NAME,
            'password' => self::PASSWORD,
        ]);
    }
}
