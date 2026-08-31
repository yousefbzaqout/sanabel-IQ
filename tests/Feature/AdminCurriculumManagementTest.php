<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LearningMaterial;
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
}
