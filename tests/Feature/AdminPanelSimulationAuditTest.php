<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full HTTP walk of Filament admin surfaces for tenant admin + platform admin.
 */
class AdminPanelSimulationAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * School tenant-admin surfaces (sales lead inbox is platform-only).
     *
     * @return list<string>
     */
    private function tenantAdminPaths(): array
    {
        return [
            '/admin',
            '/admin/users',
            '/admin/students',
            '/admin/subjects',
            '/admin/learning-materials',
            '/admin/questions',
            '/admin/interactive-lessons',
            '/admin/lesson-analytics',
            '/admin/tenants',
            '/admin/tenant-settings',
        ];
    }

    /**
     * @return list<string>
     */
    private function platformOnlyPaths(): array
    {
        return [
            '/admin/demo-requests',
        ];
    }

    public function test_tenant_admin_can_open_all_core_admin_pages(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $admin = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::ADMIN_EMAIL)
            ->firstOrFail();

        $failures = [];

        foreach ($this->tenantAdminPaths() as $path) {
            $response = $this->actingAs($admin)->get($path);
            $status = $response->getStatusCode();

            if ($status >= 400) {
                $failures[] = "{$path} => {$status}";
            }
        }

        $this->assertSame([], $failures, 'Tenant admin admin-page failures: '.implode(', ', $failures));

        foreach ($this->platformOnlyPaths() as $path) {
            $this->actingAs($admin)->get($path)->assertForbidden();
        }
    }

    public function test_platform_admin_can_open_all_core_admin_pages(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $platformAdmin = User::factory()->admin()->create([
            'email' => 'platform-admin@sanabel.test',
        ]);

        $failures = [];
        $paths = array_merge($this->tenantAdminPaths(), $this->platformOnlyPaths());

        foreach ($paths as $path) {
            $response = $this->actingAs($platformAdmin)->get($path);
            $status = $response->getStatusCode();

            if ($status >= 400) {
                $failures[] = "{$path} => {$status}";
            }
        }

        $this->assertSame([], $failures, 'Platform admin admin-page failures: '.implode(', ', $failures));
    }

    public function test_teacher_and_parent_remain_forbidden_on_admin(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $parent = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::STUDENT_EMAIL)
            ->firstOrFail();

        $this->actingAs($teacher)->get('/admin')->assertForbidden();
        $this->actingAs($parent)->get('/admin')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/users')->assertForbidden();
        $this->actingAs($parent)->get('/admin/subjects')->assertForbidden();
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
