<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantOnboardingService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_onboard_creates_tenant_admin_staff_lessons_and_mock_analytics(): void
    {
        $lessonA = $this->seedPublishedLesson('ar-g1-onboard-a', 'درس أ');
        $lessonB = $this->seedPublishedLesson('ar-g1-onboard-b', 'درس ب');
        $this->seedPublishedLesson('ar-g1-draft-skip', 'مسودة', status: 'draft');

        $tenant = app(TenantOnboardingService::class)->onboard([
            'name' => 'مدرسة الأمل النموذجية',
            'slug' => 'alamal-model-school',
            'domain' => 'alamal.sanabel.test',
            'admin_email' => 'admin@alamal.sanabel.test',
            'admin_name' => 'مدير الأمل',
            'teacher_email' => 'teacher@alamal.sanabel.test',
            'teacher_name' => 'معلم الأمل',
            'student_email' => 'parent@alamal.sanabel.test',
            'student_name' => 'ولي أمر الأمل',
            'student_display_name' => 'سارة الأمل',
            'password' => 'password',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame('مدرسة الأمل النموذجية', $tenant->name);
        $this->assertSame('alamal-model-school', $tenant->slug);
        $this->assertSame('alamal.sanabel.test', $tenant->domain);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);

        $admin = User::query()->withoutTenantScope()->where('email', 'admin@alamal.sanabel.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::TenantAdmin, $admin->role);
        $this->assertSame($tenant->id, $admin->tenant_id);
        $this->assertTrue(Hash::check('password', $admin->password));

        $teacher = User::query()->withoutTenantScope()->where('email', 'teacher@alamal.sanabel.test')->first();
        $this->assertNotNull($teacher);
        $this->assertSame(UserRole::Teacher, $teacher->role);
        $this->assertSame($tenant->id, $teacher->tenant_id);

        $parent = User::query()->withoutTenantScope()->where('email', 'parent@alamal.sanabel.test')->first();
        $this->assertNotNull($parent);
        $this->assertSame(UserRole::Parent, $parent->role);
        $this->assertSame($tenant->id, $parent->tenant_id);

        $student = Student::query()
            ->where('user_id', $parent->id)
            ->where('name', 'سارة الأمل')
            ->first();
        $this->assertNotNull($student);

        $this->assertSame($tenant->id, $lessonA->fresh()->tenant_id);
        $this->assertSame($tenant->id, $lessonB->fresh()->tenant_id);
        $this->assertNull(
            InteractiveLesson::query()->withoutTenantScope()->where('lesson_key', 'ar-g1-draft-skip')->value('tenant_id'),
        );

        $analytics = LessonAnalytic::query()
            ->withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $student->id)
            ->get();

        $this->assertGreaterThan(0, $analytics->count());
        $this->assertTrue($analytics->contains(fn (LessonAnalytic $row): bool => $row->event_type === 'lesson_complete'));
        $this->assertTrue($analytics->contains(fn (LessonAnalytic $row): bool => $row->event_type === 'voice_attempt'));
        $this->assertTrue(
            $analytics->pluck('interactive_lesson_id')->filter()->intersect([$lessonA->id, $lessonB->id])->isNotEmpty(),
        );

        $this->assertNull(TenantContext::id());
    }

    public function test_b2b_demo_tenant_seeder_provisions_alamal_school(): void
    {
        $this->seedPublishedLesson('ar-g1-letter-raa', 'حرف الراء');
        $this->seedPublishedLesson('ar-g1-math-number-3', 'العدد 3');

        $this->seed(B2bDemoTenantSeeder::class);

        $tenant = Tenant::query()->where('domain', B2bDemoTenantSeeder::DOMAIN)->first();
        $this->assertNotNull($tenant);
        $this->assertSame(B2bDemoTenantSeeder::NAME, $tenant->name);
        $this->assertSame(B2bDemoTenantSeeder::SLUG, $tenant->slug);

        $admin = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::ADMIN_EMAIL)
            ->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::TenantAdmin, $admin->role);
        $this->assertSame($tenant->id, $admin->tenant_id);

        $this->assertTrue(
            InteractiveLesson::query()
                ->withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'published')
                ->exists(),
        );

        $this->assertTrue(
            LessonAnalytic::query()
                ->withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->exists(),
        );
    }

    private function seedPublishedLesson(string $lessonKey, string $title, string $status = 'published'): InteractiveLesson
    {
        $material = LearningMaterial::factory()->published()->create([
            'title' => 'مادة '.$title,
        ]);

        return InteractiveLesson::query()->create([
            'tenant_id' => null,
            'learning_material_id' => $material->id,
            'lesson_key' => $lessonKey,
            'title' => $title,
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => $status,
        ]);
    }
}
