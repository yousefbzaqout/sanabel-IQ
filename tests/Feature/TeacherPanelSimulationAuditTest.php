<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\TeacherLessonAssignment;
use App\Models\User;
use Database\Seeders\B2bDemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPanelSimulationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_dashboard_lessons_and_student_progress(): void
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
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('بوابة المعلم', false)
            ->assertSee('صفي', false)
            ->assertSee('معلم الأمل', false)
            ->assertSee(B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME, false)
            ->assertSee(route('teacher.lessons.index'), false)
            ->assertSee(route('teacher.students.progress', $student), false);

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.index'))
            ->assertOk()
            ->assertSee(B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME, false);

        $this->actingAs($teacher)
            ->get(route('teacher.students.progress', $student))
            ->assertOk()
            ->assertSee($student->name, false);
    }

    public function test_teacher_can_assign_and_unassign_published_lesson_for_alamal_student(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $student = Student::query()
            ->where('name', B2bDemoTenantSeeder::STUDENT_DISPLAY_NAME)
            ->firstOrFail();

        $material = LearningMaterial::factory()->published()->create([
            'title' => 'مادة درس محاكاة المعلم',
        ]);

        $lesson = InteractiveLesson::query()->withoutTenantScope()->create([
            'tenant_id' => null,
            'learning_material_id' => $material->id,
            'lesson_key' => 'sim-teacher-assign-lesson',
            'title' => 'درس محاكاة المعلم',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.index'))
            ->assertOk()
            ->assertSee('درس محاكاة المعلم', false);

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.assign'), [
                'interactive_lesson_id' => $lesson->id,
                'student_id' => $student->id,
                'notes' => 'مهمة محاكاة المعلم',
            ])
            ->assertRedirect();

        $assignment = TeacherLessonAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('interactive_lesson_id', $lesson->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($assignment);

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.index'))
            ->assertOk()
            ->assertSee('تم تعيين الدرس بنجاح', false)
            ->assertSee($student->name, false)
            ->assertSee('إلغاء التعيين', false);

        $this->actingAs($teacher)
            ->post(route('teacher.lessons.unassign'), [
                'assignment_id' => $assignment->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('teacher_lesson_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_teacher_is_forbidden_from_admin_and_parent_panels(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $teacher = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::TEACHER_EMAIL)
            ->firstOrFail();

        $this->actingAs($teacher)->get('/admin')->assertForbidden();
        $this->actingAs($teacher)->get('/parent')->assertForbidden();
    }

    public function test_parent_and_guest_cannot_open_teacher_portal(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $parent = User::query()
            ->withoutTenantScope()
            ->where('email', B2bDemoTenantSeeder::STUDENT_EMAIL)
            ->firstOrFail();

        $this->get(route('teacher.dashboard'))->assertRedirect(route('teacher.login'));

        $this->actingAs($parent)
            ->get(route('teacher.dashboard'))
            ->assertForbidden();
    }

    public function test_teacher_login_redirects_to_teacher_home(): void
    {
        $this->seed(B2bDemoTenantSeeder::class);

        $this->post(route('login'), [
            'email' => B2bDemoTenantSeeder::TEACHER_EMAIL,
            'password' => B2bDemoTenantSeeder::PASSWORD,
            'intended_role' => 'teacher',
        ])->assertRedirect('/teacher');
    }
}
