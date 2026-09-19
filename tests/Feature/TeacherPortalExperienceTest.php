<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPortalExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_teacher_portal_link_opens_dedicated_teacher_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('teacher.login'), false)
            ->assertSee('بوابة المعلم', false);

        $this->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.login'));

        $this->get(route('teacher.login'))
            ->assertOk()
            ->assertSee('بوابة المعلم', false)
            ->assertSee('البريد الإلكتروني للمعلم', false)
            ->assertDontSee('رمز العائلة', false)
            ->assertDontSee('أولياء الأمور', false);
    }

    public function test_teacher_can_login_via_teacher_login_and_land_on_classroom(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $this->post(route('teacher.login.store'), [
            'email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
        ])->assertRedirect(route('teacher.dashboard'));

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $this->assertAuthenticatedAs($teacher);

        $this->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('بوابة المعلم', false)
            ->assertSee('صفي', false)
            ->assertSee('تعيين الدروس', false)
            ->assertDontSee('Children', false)
            ->assertDontSee('Analytics', false)
            ->assertDontSee('فعّل إشعارات الهاتف لتصلك تحديثات أطفالك', false);
    }

    public function test_parent_credentials_are_rejected_on_teacher_login(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $this->from(route('teacher.login'))
            ->post(route('teacher.login.store'), [
                'email' => B2bDemoTenantSeeder::STUDENT_EMAIL,
                'password' => B2bDemoTenantSeeder::PASSWORD,
            ])
            ->assertRedirect(route('teacher.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_teacher_shell_is_used_on_lessons_and_progress_pages(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $student = Student::query()
            ->where('name', B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME)
            ->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.index'))
            ->assertOk()
            ->assertSee('تعيين الدروس', false)
            ->assertDontSee(__('Children'), false)
            ->assertDontSee('Leaderboard', false);

        $this->actingAs($teacher)
            ->get(route('teacher.students.progress', $student))
            ->assertOk()
            ->assertSee('تقرير التقدّم', false)
            ->assertSee($student->name, false)
            ->assertDontSee('Compare', false);
    }
}
