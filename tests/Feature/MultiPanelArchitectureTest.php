<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Parent\Widgets\ActiveChildSwitcherWidget;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiPanelArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_parent_is_redirected_to_filament_parent_panel_after_login(): void
    {
        $parent = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $parent->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/parent');
    }

    public function test_dashboard_route_redirects_to_parent_panel(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect('/parent');
    }

    public function test_admin_login_redirects_to_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');
    }

    public function test_parent_can_access_filament_parent_panel(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get('/parent')
            ->assertOk();
    }

    public function test_admin_cannot_access_filament_parent_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/parent')
            ->assertForbidden();
    }

    public function test_parent_cannot_access_filament_admin_panel(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_parent_can_switch_active_child_in_filament_panel(): void
    {
        $parent = User::factory()->create();
        $first = Student::factory()->for($parent)->create(['name' => 'First Child']);
        $second = Student::factory()->for($parent)->create(['name' => 'Second Child']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $first->id]);

        Livewire::test(ActiveChildSwitcherWidget::class)
            ->set('selectedStudentId', (string) $second->id)
            ->call('switchActiveChild');

        $this->assertSame($second->id, session('active_student_id'));
    }

    public function test_unauthenticated_user_cannot_access_student_hub(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
        $this->get(route('student.progress'))->assertRedirect(route('login'));
    }

    public function test_childless_parent_cannot_access_student_hub(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('onboarding.child'));
    }

    public function test_parent_with_child_can_access_student_dashboard(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => 'Learner']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Learner');
    }

    public function test_user_role_cannot_be_mass_assigned_via_fill(): void
    {
        $user = User::factory()->create();

        $user->update(['role' => UserRole::Admin->value]);

        $this->assertSame(UserRole::Parent, $user->fresh()->role);
    }

    public function test_student_report_export_returns_valid_pdf_stream(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'PDF Student',
            'total_xp' => 90,
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.students.export', [
                'student' => $student,
                'format' => 'pdf',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
