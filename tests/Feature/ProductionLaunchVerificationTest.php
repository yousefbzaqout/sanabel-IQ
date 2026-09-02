<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subject;
use Database\Seeders\GradeSubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionLaunchVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_configuration_has_debug_disabled_and_secure_session_defaults(): void
    {
        $this->assertTrue((bool) config('session.http_only'));

        $appSource = file_get_contents(config_path('app.php'));
        $sessionSource = file_get_contents(config_path('session.php'));

        $this->assertNotFalse($appSource);
        $this->assertNotFalse($sessionSource);
        $this->assertStringContainsString("env('APP_DEBUG', false)", $appSource);
        $this->assertStringContainsString(
            "env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production')",
            $sessionSource,
        );
        $this->assertStringContainsString("env('SESSION_HTTP_ONLY', true)", $sessionSource);
    }

    public function test_database_seeders_run_idempotently_without_duplicating_curriculum(): void
    {
        $this->seed(GradeSubjectSeeder::class);
        $firstRunCount = Subject::query()->count();

        $this->seed(GradeSubjectSeeder::class);
        $secondRunCount = Subject::query()->count();

        $this->assertSame(15, $firstRunCount);
        $this->assertSame($firstRunCount, $secondRunCount);

        for ($gradeLevel = 1; $gradeLevel <= 5; $gradeLevel++) {
            $this->assertSame(
                3,
                Subject::query()->where('grade_level', $gradeLevel)->count(),
                "Grade {$gradeLevel} should have exactly three normalized subjects.",
            );
        }

        $this->assertSame(
            5,
            Subject::query()->where('code', 'MATH')->count(),
        );
    }
}
