<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Services\Student\ChildLoginCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualRoleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_view_displays_only_parent_and_student_tabs(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('أولياء الأمور', false);
        $response->assertSee('الطلاب والأبناء', false);
        $response->assertSee('حساب ولي الأمر والأسرة', false);
        $response->assertSee('حساب البطل الصغير (الطالب)', false);
        $response->assertSee('البريد الإلكتروني لولي الأمر', false);
        $response->assertSee('رمز العائلة', false);
        $response->assertSee('استخدم البريد المسجل لمتابعة إنجازات أطفالك', false);
        $response->assertSee('سجّل دخولك لتبدأ المغامرة وجمع النقاط مع سنبل!', false);

        $response->assertDontSee('المعلمون والكادر', false);
        $response->assertDontSee('حساب إدارة المدرسة', false);
        $response->assertDontSee('admin_panel_settings', false);
        $response->assertDontSee("id: 'teacher'", false);
        $response->assertDontSee("id: 'admin'", false);
    }

    public function test_parent_login_redirects_to_parent_portal(): void
    {
        $parent = User::factory()->create([
            'email' => 'parent@sanabel.test',
        ]);
        $this->assertSame(UserRole::Parent, $parent->fresh()->role);

        $this->post(route('login'), [
            'email' => 'parent@sanabel.test',
            'password' => 'password',
            'intended_role' => 'parent',
        ])->assertRedirect('/onboarding/child');

        $this->assertAuthenticatedAs($parent);
    }

    public function test_student_pin_login_redirects_to_student_hub(): void
    {
        $parent = User::factory()->create();
        $child = Student::factory()->for($parent)->create(['name' => 'سارة البطلة']);

        $service = app(ChildLoginCredentialService::class);
        $code = $service->ensureFamilyCode($parent);
        $loginUser = $service->setPin($child, '2468');

        $this->post(route('login.child'), [
            'family_code' => $code,
            'student_id' => $child->id,
            'pin' => '2468',
        ])->assertRedirect('/student');

        $this->assertAuthenticatedAs($loginUser);

        $this->followingRedirects()
            ->get('/student')
            ->assertOk();
    }

    public function test_student_role_cannot_open_parent_filament_panel(): void
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
