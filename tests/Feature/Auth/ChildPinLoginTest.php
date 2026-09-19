<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Filament\Parent\Resources\Children\Pages\EditChild;
use App\Models\Student;
use App\Models\User;
use App\Services\Student\ChildLoginCredentialService;
use Database\Seeders\DualRoleLoginSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChildPinLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_set_pin_and_receives_family_code(): void
    {
        $parent = User::factory()->create(['email' => 'household@example.test']);
        $child = Student::factory()->for($parent)->create(['name' => 'ليان']);

        $service = app(ChildLoginCredentialService::class);
        $code = $service->ensureFamilyCode($parent);
        $loginUser = $service->setPin($child, '1234');

        $this->assertSame(6, strlen($code));
        $this->assertSame($code, $parent->fresh()->family_code);
        $this->assertTrue($child->fresh()->hasChildLoginEnabled());
        $this->assertSame(UserRole::Student, $loginUser->role);
        $this->assertSame($loginUser->id, $child->fresh()->login_user_id);
    }

    public function test_lookup_returns_only_that_households_children(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $childA = Student::factory()->for($parentA)->create(['name' => 'زياد']);
        Student::factory()->for($parentB)->create(['name' => 'غريب']);

        $service = app(ChildLoginCredentialService::class);
        $code = $service->ensureFamilyCode($parentA);
        $service->setPin($childA, '4321');

        $this->postJson(route('login.child.lookup'), [
            'family_code' => $code,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'children')
            ->assertJsonPath('children.0.name', 'زياد')
            ->assertJsonPath('children.0.login_enabled', true)
            ->assertJsonMissing(['name' => 'غريب']);
    }

    public function test_correct_pin_logs_child_into_student_hub(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create(['name' => 'سارة']);

        $service = app(ChildLoginCredentialService::class);
        $code = $service->ensureFamilyCode($parent);
        $loginUser = $service->setPin($child, '2468');

        $this->post(route('login.child'), [
            'family_code' => $code,
            'student_id' => $child->id,
            'pin' => '2468',
        ])
            ->assertRedirect('/student');

        $this->assertAuthenticatedAs($loginUser);
        $this->assertSame($child->id, (int) session('active_student_id'));

        $this->get(route('student.dashboard'))->assertOk();
    }

    public function test_wrong_pin_keeps_guest_and_returns_error(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create();

        $service = app(ChildLoginCredentialService::class);
        $code = $service->ensureFamilyCode($parent);
        $service->setPin($child, '1111');

        $this->from(route('login'))
            ->post(route('login.child'), [
                'family_code' => $code,
                'student_id' => $child->id,
                'pin' => '9999',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_child_login_user_cannot_open_parent_panel(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create();

        $loginUser = app(ChildLoginCredentialService::class)->setPin($child, '1357');

        $this->actingAs($loginUser)
            ->withSession(['active_student_id' => $child->id])
            ->get('/parent')
            ->assertForbidden();
    }

    public function test_parent_email_login_still_redirects_to_parent(): void
    {
        $parent = User::factory()->create([
            'email' => DualRoleLoginSeeder::PARENT_EMAIL,
        ]);

        $this->post(route('login'), [
            'email' => DualRoleLoginSeeder::PARENT_EMAIL,
            'password' => 'password',
            'intended_role' => 'parent',
        ])->assertRedirect('/onboarding/child');

        $this->assertAuthenticatedAs($parent);
    }

    public function test_login_view_shows_family_code_and_pin_student_flow(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('رمز العائلة', false)
            ->assertSee('family_code', false)
            ->assertSee('الطلاب والأبناء', false);
    }

    public function test_parent_can_assign_pin_from_filament_edit_child_form(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create([
            'name' => 'زياد',
            'grade_level' => 1,
            'school_term' => 1,
        ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $child->id]);

        Filament::setCurrentPanel(Filament::getPanel('parent'));

        Livewire::test(EditChild::class, ['record' => $child->getKey()])
            ->fillForm([
                'login_pin' => '2468',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $child->refresh();
        $this->assertTrue($child->hasChildLoginEnabled());
        $this->assertTrue(
            app(ChildLoginCredentialService::class)->verifyPin($child, '2468'),
        );
    }
}
