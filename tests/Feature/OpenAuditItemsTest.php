<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\TenantSettings;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\TeacherLessonAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantSeatLimitService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpenAuditItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_teacher_can_view_published_catalog_lessons_and_assign_to_classroom(): void
    {
        [$tenant, $teacher, $student] = $this->seedTenantWithTeacherAndStudent();
        $lesson = $this->seedPublishedLesson('ar-g1-math-addition', 'جمع الأعداد');

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.index'))
            ->assertOk()
            ->assertSee('جمع الأعداد')
            ->assertSee($student->name);

        $response = $this->actingAs($teacher)
            ->post(route('teacher.lessons.assign'), [
                'interactive_lesson_id' => $lesson->id,
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'واجب نهاية الأسبوع لجميع طلاب الصف',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teacher_lesson_assignments', [
            'tenant_id' => $tenant->id,
            'teacher_id' => $teacher->id,
            'interactive_lesson_id' => $lesson->id,
            'student_id' => null,
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_assign_lesson_to_specific_student_and_unassign(): void
    {
        [$tenant, $teacher, $student] = $this->seedTenantWithTeacherAndStudent();
        $lesson = $this->seedPublishedLesson('ar-g1-science-plants', 'النباتات');

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.assign'), [
                'interactive_lesson_id' => $lesson->id,
                'student_id' => $student->id,
                'notes' => 'مهمة دعم إضافية لسارة',
            ])
            ->assertRedirect();

        $assignment = TeacherLessonAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->where('interactive_lesson_id', $lesson->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame($teacher->id, $assignment->teacher_id);

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.unassign'), [
                'assignment_id' => $assignment->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('teacher_lesson_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_teacher_cannot_assign_lesson_to_student_of_another_school(): void
    {
        [, $teacherA] = $this->seedTenantWithTeacherAndStudent('مدرسة أ', 'school-a');

        $tenantB = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $parentB = User::factory()->create(['tenant_id' => $tenantB->id]);
        $studentB = Student::factory()->for($parentB)->create(['name' => 'طالب مدرسة ب']);

        $lesson = $this->seedPublishedLesson('ar-g1-cross-school', 'درس تجريبي');

        $this->actingAs($teacherA)
            ->post(route('teacher.lessons.assign'), [
                'interactive_lesson_id' => $lesson->id,
                'student_id' => $studentB->id,
            ])
            ->assertForbidden();
    }

    public function test_tenant_admin_can_update_school_branding_and_settings_via_filament_page(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة الأمل النموذجية',
            'slug' => 'alamal',
            'domain' => 'alamal.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
            'primary_color' => '#10B981',
            'seat_limit' => 50,
        ]);

        $admin = User::factory()->tenantAdmin()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin@alamal.test',
        ]);

        Livewire::actingAs($admin)
            ->test(TenantSettings::class)
            ->assertSuccessful()
            ->fillForm([
                'name' => 'مدرسة الأمل الحديثة',
                'slug' => 'alamal-modern',
                'domain' => 'portal.alamal.edu.ps',
                'logo_path' => 'https://alamal.edu.ps/logo.png',
                'primary_color' => '#6366F1',
                'contact_email' => 'info@alamal.edu.ps',
                'contact_phone' => '+970599123456',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $tenant->refresh();
        $this->assertSame('مدرسة الأمل الحديثة', $tenant->name);
        $this->assertSame('alamal-modern', $tenant->slug);
        $this->assertSame('portal.alamal.edu.ps', $tenant->domain);
        $this->assertSame('https://alamal.edu.ps/logo.png', $tenant->logo_path);
        $this->assertSame('#6366F1', $tenant->primary_color);
        $this->assertSame('info@alamal.edu.ps', $tenant->contact_email);
        $this->assertSame('+970599123456', $tenant->contact_phone);
    }

    public function test_tenant_admin_cannot_edit_settings_of_foreign_school_via_resource(): void
    {
        $tenantA = Tenant::query()->create([
            'name' => 'مدرسة أ',
            'slug' => 'tenant-a',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'tenant-b',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $adminA = User::factory()->tenantAdmin()->create([
            'tenant_id' => $tenantA->id,
            'email' => 'admin@tenant-a.test',
        ]);

        Livewire::actingAs($adminA)
            ->test(ListTenants::class)
            ->assertCanSeeTableRecords([$tenantA])
            ->assertCanNotSeeTableRecords([$tenantB]);

        Livewire::actingAs($adminA)
            ->test(EditTenant::class, ['record' => $tenantB->getKey()])
            ->assertForbidden();
    }

    public function test_seat_limit_guard_allows_student_creation_within_quota(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة النور',
            'slug' => 'al-noor',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 3,
        ]);

        $parent = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(TenantSeatLimitService::class);
        $this->assertTrue($service->hasAvailableSeats($tenant));
        $this->assertSame(3, $service->getSeatLimit($tenant));

        $this->actingAs($parent)
            ->post(route('students.store'), [
                'name' => 'طالب 1',
                'grade_level' => 1,
                'school_term' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, $service->getStudentCount($tenant));
        $this->assertSame(2, $service->getRemainingSeats($tenant));
    }

    public function test_seat_limit_guard_blocks_creation_when_quota_is_exceeded(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة القدس',
            'slug' => 'al-quds',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 2,
        ]);

        $parent = User::factory()->create(['tenant_id' => $tenant->id]);

        Student::factory()->for($parent)->create(['name' => 'طالب سابق 1']);
        Student::factory()->for($parent)->create(['name' => 'طالب سابق 2']);

        $service = app(TenantSeatLimitService::class);
        $this->assertFalse($service->hasAvailableSeats($tenant));
        $this->assertSame(0, $service->getRemainingSeats($tenant));

        $response = $this->actingAs($parent)
            ->post(route('students.store'), [
                'name' => 'طالب جديد زائد',
                'grade_level' => 1,
                'school_term' => 1,
            ]);

        $response->assertSessionHasErrors(['seat_limit']);

        $this->assertDatabaseMissing('students', [
            'name' => 'طالب جديد زائد',
        ]);
    }

    public function test_tenant_seat_limit_service_provides_detailed_quota_metrics(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة يافا',
            'slug' => 'yafa',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 10,
        ]);

        $parent = User::factory()->create(['tenant_id' => $tenant->id]);
        Student::factory()->count(4)->for($parent)->create();

        $service = app(TenantSeatLimitService::class);
        $metrics = $service->getSeatMetrics($tenant);

        $this->assertSame(4, $metrics['used']);
        $this->assertSame(10, $metrics['limit']);
        $this->assertSame(6, $metrics['remaining']);
        $this->assertSame(40.0, $metrics['usage_percentage']);
        $this->assertFalse($metrics['is_limit_reached']);
    }

    public function test_filament_create_student_enforces_seat_limit(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة حيفا',
            'slug' => 'haifa',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 1,
        ]);

        $admin = User::factory()->tenantAdmin()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin@haifa.test',
        ]);

        $parent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'والد في حيفا',
        ]);

        Student::factory()->for($parent)->create(['name' => 'طالب موجود']);

        Livewire::actingAs($admin)
            ->test(CreateStudent::class)
            ->fillForm([
                'name' => 'طالب إضافي مرفوض',
                'user_id' => $parent->id,
                'grade_level' => 1,
                'school_term' => 1,
            ])
            ->call('create')
            ->assertHasErrors(['name']);

        $this->assertDatabaseMissing('students', [
            'name' => 'طالب إضافي مرفوض',
        ]);
    }

    public function test_super_admin_can_update_any_tenant_settings_and_quota(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة الخليل',
            'slug' => 'hebron',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 20,
        ]);

        $superAdmin = User::factory()->admin()->create();

        Livewire::actingAs($superAdmin)
            ->test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => 'مدرسة الخليل الأهلية',
                'slug' => 'hebron-ahli',
                'seat_limit' => 100,
                'subscription_plan' => 'enterprise',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $tenant->refresh();
        $this->assertSame('مدرسة الخليل الأهلية', $tenant->name);
        $this->assertSame('hebron-ahli', $tenant->slug);
        $this->assertSame(100, $tenant->seat_limit);
        $this->assertSame('enterprise', $tenant->subscription_plan);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Student}
     */
    private function seedTenantWithTeacherAndStudent(string $name = 'مدرسة تجريبية', string $slug = 'school-demo'): array
    {
        $tenant = Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 50,
        ]);

        $teacher = User::factory()->teacher()->create([
            'name' => 'المعلم أحمد',
            'email' => 'teacher@'.$slug.'.test',
            'tenant_id' => $tenant->id,
        ]);

        $parent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'parent@'.$slug.'.test',
        ]);

        $student = Student::factory()->for($parent)->create([
            'name' => 'سارة التجريبية',
            'grade_level' => 1,
        ]);

        return [$tenant, $teacher, $student];
    }

    private function seedPublishedLesson(string $lessonKey, string $title): InteractiveLesson
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
            'status' => 'published',
        ]);
    }
}
