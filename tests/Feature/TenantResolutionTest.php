<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_subdomain_host_populates_tenant_context(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة أ',
            'slug' => 'school-a',
            'domain' => 'school-a.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $parent = User::factory()->create([
            'email' => 'parent@school-a.test',
            'tenant_id' => $tenant->id,
        ]);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get($this->tenantUrl('school-a.sanabel.test', route('parent.analytics', absolute: false)));

        $this->assertTrue(TenantContext::check());
        $this->assertSame($tenant->id, TenantContext::id());
        $this->assertTrue(TenantContext::tenant()?->is($tenant));
    }

    public function test_slug_subdomain_resolves_tenant_without_exact_domain_column_match(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b',
            'domain' => null,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $parent = User::factory()->create([
            'email' => 'parent@school-b.test',
            'tenant_id' => $tenant->id,
        ]);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get($this->tenantUrl('school-b.example.test', route('student.dashboard', absolute: false)));

        $this->assertSame($tenant->id, TenantContext::id());
    }

    public function test_authenticated_user_tenant_is_used_on_central_host(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة ج',
            'slug' => 'school-c',
            'domain' => 'school-c.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $parent = User::factory()->create([
            'email' => 'parent@school-c.test',
            'tenant_id' => $tenant->id,
        ]);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get($this->tenantUrl('localhost', route('parent.analytics', absolute: false)));

        $this->assertSame($tenant->id, TenantContext::id());
    }

    public function test_inactive_tenant_domain_returns_forbidden(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة موقوفة',
            'slug' => 'school-inactive',
            'domain' => 'school-inactive.sanabel.test',
            'status' => Tenant::STATUS_INACTIVE,
        ]);

        $parent = User::factory()->create([
            'email' => 'parent@inactive.test',
            'tenant_id' => $tenant->id,
        ]);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get($this->tenantUrl('school-inactive.sanabel.test', route('parent.analytics', absolute: false)))
            ->assertForbidden();
    }

    public function test_unknown_tenant_subdomain_returns_not_found(): void
    {
        $parent = User::factory()->create(['email' => 'parent@unknown.test']);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get($this->tenantUrl('missing-school.sanabel.test', route('parent.analytics', absolute: false)))
            ->assertNotFound();
    }

    public function test_teacher_admin_route_resolves_tenant_from_user(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة المعلم',
            'slug' => 'school-teacher',
            'domain' => 'school-teacher.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $teacher = User::factory()->admin()->create([
            'email' => 'teacher@school-teacher.test',
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($teacher)
            ->getJson($this->tenantUrl('localhost', route('admin.mastery-analytics.data', absolute: false)))
            ->assertOk();

        $this->assertSame($tenant->id, TenantContext::id());
    }

    private function tenantUrl(string $host, string $path): string
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return 'http://'.$host.$path;
    }
}
