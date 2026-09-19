<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Student;
use App\Models\User;
use App\Services\Student\ChildLoginCredentialService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdultsPortalSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_child_session_leaves_student_hub_and_opens_parent_login(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create(['name' => 'زياد']);
        $loginUser = app(ChildLoginCredentialService::class)->setPin($child, '2468');

        $this->actingAs($loginUser)
            ->withSession(['active_student_id' => $child->id])
            ->get(route('student.adults-portal'))
            ->assertRedirect(route('login', ['role' => 'parent']));

        $this->assertGuest();
        $this->assertNull(session('url.intended'));
    }

    public function test_parent_login_after_adults_portal_switch_reaches_parent_home(): void
    {
        $parent = User::factory()->create([
            'email' => 'household-parent@example.test',
        ]);
        $child = Student::factory()->for($parent)->create();
        $loginUser = app(ChildLoginCredentialService::class)->setPin($child, '2468');

        $this->actingAs($loginUser)
            ->withSession(['active_student_id' => $child->id])
            ->get(route('student.adults-portal'))
            ->assertRedirect(route('login', ['role' => 'parent']));

        $this->post(route('login'), [
            'email' => 'household-parent@example.test',
            'password' => 'password',
            'intended_role' => 'parent',
        ])->assertRedirect('/parent');

        $this->assertAuthenticatedAs($parent);
    }

    public function test_stale_parent_intended_does_not_break_tenant_admin_admin_home(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $admin = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::ADMIN_EMAIL)
            ->firstOrFail();

        $this->withSession(['url.intended' => url('/parent')])
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_parent_session_goes_straight_to_parent_panel(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('student.adults-portal'))
            ->assertRedirect('/parent');

        $this->assertAuthenticatedAs($parent);
    }

    public function test_student_dashboard_links_to_adults_portal_switch_not_parent_panel(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create();
        $loginUser = app(ChildLoginCredentialService::class)->setPin($child, '1357');

        $this->actingAs($loginUser)
            ->withSession(['active_student_id' => $child->id])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee(route('student.adults-portal'), false)
            ->assertDontSee('href="'.url('/parent').'"', false);
    }

    public function test_login_page_opens_parent_tab_when_role_query_is_parent(): void
    {
        $this->get(route('login', ['role' => 'parent']))
            ->assertOk()
            ->assertSee("role: 'parent'", false);
    }

    public function test_child_login_user_still_cannot_open_parent_panel_directly(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create();
        $loginUser = app(ChildLoginCredentialService::class)->setPin($child, '1357');

        $this->actingAs($loginUser)
            ->withSession(['active_student_id' => $child->id])
            ->get('/parent')
            ->assertForbidden();
    }
}
