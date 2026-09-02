<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurriculumManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_admin_curriculum_routes(): void
    {
        $parent = User::factory()->create(['role' => UserRole::Parent]);
        $subject = Subject::factory()->create(['code' => 'MATH-G3', 'grade_level' => 3]);
        $material = LearningMaterial::factory()->for($subject)->create();

        $this->actingAs($parent)
            ->get(route('admin.subjects.index'))
            ->assertForbidden();

        $this->actingAs($parent)
            ->post(route('admin.subjects.store'), [
                'name' => 'رياضيات',
                'code' => 'MATH-G3',
                'grade_level' => 3,
            ])
            ->assertForbidden();

        $this->actingAs($parent)
            ->put(route('admin.materials.update', $material), [
                'title' => 'Tampered',
                'xp_reward' => 50,
                'is_published' => true,
            ])
            ->assertForbidden();

        $this->actingAs($parent)
            ->post(route('admin.materials.reorder'), [
                'ordered_ids' => [$material->id],
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_access_admin_curriculum_routes(): void
    {
        $this->get(route('admin.subjects.index'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.subjects.store'), [
            'name' => 'رياضيات',
            'code' => 'MATH-G1',
            'grade_level' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_admin_can_create_and_update_subject_mapped_to_grade(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'name' => 'رياضيات',
                'code' => 'MATH-G3',
                'grade_level' => 3,
                'icon' => 'calculator',
                'description' => 'منهج الصف الثالث',
            ])
            ->assertRedirect(route('admin.subjects.index', ['grade' => 3]));

        $this->assertDatabaseHas('subjects', [
            'name' => 'رياضيات',
            'code' => 'MATH-G3',
            'grade_level' => 3,
            'icon' => 'calculator',
            'description' => 'منهج الصف الثالث',
        ]);

        $subject = Subject::query()->where('code', 'MATH-G3')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.subjects.update', $subject), [
                'name' => 'رياضيات متقدمة',
                'code' => 'MATH-G3',
                'grade_level' => 3,
                'icon' => 'calculator',
                'description' => 'منهج محدث',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'رياضيات متقدمة',
            'description' => 'منهج محدث',
        ]);
    }

    public function test_admin_can_create_learning_material_under_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create([
            'name' => 'علوم',
            'code' => 'SCI-G2',
            'grade_level' => 2,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subjects.materials.store', $subject), [
                'title' => 'وحدة الكائنات الحية',
                'description' => 'مقدمة عن النباتات',
                'xp_reward' => 100,
                'is_published' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_materials', [
            'subject_id' => $subject->id,
            'title' => 'وحدة الكائنات الحية',
            'description' => 'مقدمة عن النباتات',
            'xp_reward' => 100,
            'is_published' => true,
        ]);
    }

    public function test_admin_can_reorder_learning_materials(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create([
            'code' => 'AR-G1',
            'grade_level' => 1,
        ]);

        $first = LearningMaterial::factory()->for($subject)->create(['title' => 'أول', 'order_column' => 1]);
        $second = LearningMaterial::factory()->for($subject)->create(['title' => 'ثاني', 'order_column' => 2]);
        $third = LearningMaterial::factory()->for($subject)->create(['title' => 'ثالث', 'order_column' => 3]);

        $this->actingAs($admin)
            ->post(route('admin.materials.reorder'), [
                'ordered_ids' => [$third->id, $first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, $third->fresh()?->order_column);
        $this->assertSame(2, $first->fresh()?->order_column);
        $this->assertSame(3, $second->fresh()?->order_column);
    }

    public function test_draft_learning_materials_are_hidden_from_parent_curriculum_feed(): void
    {
        $parent = User::factory()->create(['role' => UserRole::Parent]);
        $student = Student::factory()->for($parent)->create();
        $subject = Subject::factory()->create(['code' => 'SCI-G4', 'grade_level' => 4]);

        $published = LearningMaterial::factory()->for($subject)->published()->create([
            'title' => 'مادة منشورة',
            'order_column' => 1,
        ]);
        $draft = LearningMaterial::factory()->for($subject)->create([
            'title' => 'مادة مسودة',
            'is_published' => false,
            'order_column' => 2,
        ]);

        $response = $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->getJson(route('parent.curriculum.materials', $subject));

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');

        $this->assertTrue($titles->contains($published->title));
        $this->assertFalse($titles->contains($draft->title));
        $this->assertDatabaseHas('learning_materials', [
            'id' => $draft->id,
            'is_published' => false,
        ]);
    }

    public function test_duplicate_subject_code_is_rejected_for_same_grade_but_allowed_for_other_grade(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'name' => 'رياضيات صف أول',
                'code' => 'MATH-G1',
                'grade_level' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.subjects.index', ['grade' => 1]))
            ->post(route('admin.subjects.store'), [
                'name' => 'رياضيات مكررة',
                'code' => 'MATH-G1',
                'grade_level' => 1,
            ])
            ->assertSessionHasErrors('code')
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'name' => 'رياضيات صف ثان',
                'code' => 'MATH-G1',
                'grade_level' => 2,
            ])
            ->assertRedirect(route('admin.subjects.index', ['grade' => 2]));

        $this->assertDatabaseCount('subjects', 2);
        $this->assertDatabaseHas('subjects', [
            'code' => 'MATH-G1',
            'grade_level' => 2,
            'name' => 'رياضيات صف ثان',
        ]);
    }

    public function test_reorder_rejects_invalid_or_cross_subject_ids_without_corrupting_order(): void
    {
        $admin = User::factory()->admin()->create();
        $subjectA = Subject::factory()->create(['code' => 'MATH-G5', 'grade_level' => 5]);
        $subjectB = Subject::factory()->create(['code' => 'SCI-G5', 'grade_level' => 5]);

        $a1 = LearningMaterial::factory()->for($subjectA)->create(['title' => 'A1', 'order_column' => 1]);
        $a2 = LearningMaterial::factory()->for($subjectA)->create(['title' => 'A2', 'order_column' => 2]);
        $b1 = LearningMaterial::factory()->for($subjectB)->create(['title' => 'B1', 'order_column' => 1]);

        $this->actingAs($admin)
            ->from(route('admin.subjects.show', $subjectA))
            ->post(route('admin.materials.reorder'), [
                'ordered_ids' => [$a2->id, $b1->id],
            ])
            ->assertSessionHasErrors('ordered_ids')
            ->assertRedirect();

        $this->assertSame(1, $a1->fresh()?->order_column);
        $this->assertSame(2, $a2->fresh()?->order_column);
        $this->assertSame(1, $b1->fresh()?->order_column);

        $this->actingAs($admin)
            ->from(route('admin.subjects.show', $subjectA))
            ->post(route('admin.materials.reorder'), [
                'ordered_ids' => [$a2->id, 999999],
            ])
            ->assertSessionHasErrors()
            ->assertRedirect();

        $this->assertSame(1, $a1->fresh()?->order_column);
        $this->assertSame(2, $a2->fresh()?->order_column);

        $this->actingAs($admin)
            ->from(route('admin.subjects.show', $subjectA))
            ->post(route('admin.materials.reorder'), [
                'ordered_ids' => [$a2->id],
            ])
            ->assertSessionHasErrors('ordered_ids')
            ->assertRedirect();

        $this->assertSame(1, $a1->fresh()?->order_column);
        $this->assertSame(2, $a2->fresh()?->order_column);
    }
}
