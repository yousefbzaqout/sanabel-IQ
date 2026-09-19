<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\InteractiveLessons\Pages\ListInteractiveLessons;
use App\Filament\Resources\LessonAnalytics\Pages\ListLessonAnalytics;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantOnboardingService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class MultiPersonaE2eAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_tenant_admin_reaches_filament_and_cannot_see_foreign_tenant_users(): void
    {
        [$tenantA, $adminA, $foreignUser] = $this->seedAlamalAndForeignTenant();

        $this->actingAs($adminA)
            ->post('/login', [
                'email' => B2bDemoTenantSeeder::ADMIN_EMAIL,
                'password' => B2bDemoTenantSeeder::PASSWORD,
            ]);

        // Already authenticated via actingAs; verify panel routes resolve without 500.
        $this->actingAs($adminA)
            ->get('/admin')
            ->assertOk();

        Livewire::actingAs($adminA)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([
                User::query()->withoutTenantScope()->whereKey($adminA->id)->firstOrFail(),
            ])
            ->assertCanNotSeeTableRecords([$foreignUser]);

        Livewire::actingAs($adminA)
            ->test(ListInteractiveLessons::class)
            ->assertSuccessful();

        Livewire::actingAs($adminA)
            ->test(ListLessonAnalytics::class)
            ->assertSuccessful();

        $this->assertSame($tenantA->id, TenantContext::id());
    }

    public function test_teacher_classroom_dashboard_lists_tenant_students_only(): void
    {
        [, $adminA] = $this->seedAlamalAndForeignTenant();
        unset($adminA);

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $this->assertSame(UserRole::Teacher, $teacher->role);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('بوابة المعلم')
            ->assertSee('سارة الأمل');

        $foreignStudent = Student::factory()->for(
            User::factory()->create([
                'email' => 'parent@other-school.test',
                'tenant_id' => Tenant::query()->where('slug', 'other-school')->value('id'),
            ]),
        )->create(['name' => 'طالب مدرسة أخرى']);

        $this->actingAs($teacher)
            ->get(route('teacher.students.progress', $foreignStudent))
            ->assertForbidden();

        $localStudent = Student::query()
            ->where('name', B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME)
            ->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('teacher.students.progress', $localStudent))
            ->assertOk()
            ->assertSee($localStudent->name);

        $this->actingAs($teacher)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_parent_can_open_portal_and_mastery_analytics_for_child(): void
    {
        $this->seedAlamalAndForeignTenant();

        $parent = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::STUDENT_EMAIL)
            ->firstOrFail();

        $student = Student::query()
            ->where('user_id', $parent->id)
            ->where('name', B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME)
            ->firstOrFail();

        $this->actingAs($parent)
            ->get('/parent')
            ->assertOk();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.mastery-analytics.show', $student))
            ->assertOk();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_student_lesson_alias_and_ai_rate_limit_contract(): void
    {
        $this->seedAlamalAndForeignTenant();

        $parent = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::STUDENT_EMAIL)
            ->firstOrFail();
        $student = Student::query()
            ->where('user_id', $parent->id)
            ->firstOrFail();

        $lesson = InteractiveLesson::query()
            ->withoutTenantScope()
            ->where('tenant_id', $parent->tenant_id)
            ->where('status', 'published')
            ->firstOrFail();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.lessons.show', $lesson->id))
            ->assertRedirect(route('student.interactive-lesson.show', [
                'lessonKey' => $lesson->lesson_key,
            ]));

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('student.interactive-lesson.show', ['lessonKey' => $lesson->lesson_key]))
            ->assertOk();

        RateLimiter::clear('ai-voice:'.$student->id);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($parent)
                ->withSession(['active_student_id' => $student->id])
                ->postJson(route('student.ai.pronunciation'), [
                    'target' => 'رَ',
                    'transcript' => 'رَ',
                ])
                ->assertOk();
        }

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ])
            ->assertStatus(429)
            ->assertJsonPath('error', 'too_many_requests')
            ->assertJsonStructure(['message', 'error']);
    }

    public function test_super_admin_retains_cross_tenant_admin_access(): void
    {
        [$tenantA] = $this->seedAlamalAndForeignTenant();

        $super = User::factory()->admin()->create([
            'email' => 'super@sanabel.test',
            'tenant_id' => null,
        ]);

        $this->actingAs($super)
            ->get('/admin')
            ->assertOk();

        Livewire::actingAs($super)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords(
                User::query()->withoutTenantScope()->where('tenant_id', $tenantA->id)->limit(1)->get()->all(),
            );

        $this->assertNull(TenantContext::id());
    }

    public function test_login_redirect_ignores_inaccessible_intended_admin_path(): void
    {
        $this->seedAlamalAndForeignTenant();

        $this->withSession(['url.intended' => '/admin'])
            ->post('/login', [
                'email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
                'password' => B2bDemoTenantSeeder::PASSWORD,
            ])
            ->assertRedirect('/teacher');

        $this->post('/logout');

        $this->withSession(['url.intended' => '/admin'])
            ->post('/login', [
                'email' => B2bDemoTenantSeeder::STUDENT_EMAIL,
                'password' => B2bDemoTenantSeeder::PASSWORD,
            ])
            ->assertRedirect('/parent');
    }

    /**
     * @return array{0: Tenant, 1: User, 2: User}
     */
    private function seedAlamalAndForeignTenant(): array
    {
        $material = LearningMaterial::factory()->published()->create();
        InteractiveLesson::query()->create([
            'tenant_id' => null,
            'learning_material_id' => $material->id,
            'lesson_key' => 'ar-g1-audit-lesson',
            'title' => 'درس التدقيق',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'published',
        ]);

        app(TenantOnboardingService::class)->onboard([
            'name' => B2bDemoTenantSeeder::NAME,
            'slug' => B2bDemoTenantSeeder::SLUG,
            'domain' => B2bDemoTenantSeeder::DOMAIN,
            'admin_email' => B2bDemoTenantSeeder::ADMIN_EMAIL,
            'admin_name' => 'مدير الأمل',
            'teacher_email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
            'teacher_name' => 'معلم الأمل',
            'student_email' => B2bDemoTenantSeeder::STUDENT_EMAIL,
            'student_name' => 'ولي أمر الأمل',
            'student_display_name' => B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME,
            'password' => B2bDemoTenantSeeder::PASSWORD,
        ]);

        $tenantA = Tenant::query()->where('slug', B2bDemoTenantSeeder::SLUG)->firstOrFail();
        $adminA = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::ADMIN_EMAIL)
            ->firstOrFail();

        $other = Tenant::query()->create([
            'name' => 'مدرسة أخرى',
            'slug' => 'other-school',
            'domain' => 'other.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $foreignUser = User::factory()->create([
            'email' => 'spy@other-school.test',
            'tenant_id' => $other->id,
            'name' => 'مستخدم أجنبي',
        ]);

        return [$tenantA, $adminA, $foreignUser];
    }
}
