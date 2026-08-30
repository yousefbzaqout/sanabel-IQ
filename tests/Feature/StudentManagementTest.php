<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\Activity;
use App\Models\ParentMaterial;
use App\Models\RewardContract;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_parent_can_create_multiple_children(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->post(route('students.store'), [
                'name' => 'Amina',
                'grade_level' => 2,
                'school_term' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->actingAs($parent)
            ->post(route('students.store'), [
                'name' => 'Omar',
                'grade_level' => 5,
                'school_term' => 2,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'user_id' => $parent->id,
            'name' => 'Amina',
            'grade_level' => 2,
            'school_term' => 1,
            'total_xp' => 0,
            'coins' => 0,
            'lives' => 3,
        ]);

        $this->assertDatabaseHas('students', [
            'user_id' => $parent->id,
            'name' => 'Omar',
            'grade_level' => 5,
            'school_term' => 2,
        ]);

        $this->assertSame(2, $parent->students()->count());
    }

    public function test_authenticated_parent_can_update_child_profile(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Amina',
            'grade_level' => 2,
            'school_term' => 1,
            'avatar_path' => null,
        ]);

        $this->actingAs($parent)
            ->patch(route('students.update', $student), [
                'name' => 'Amina Hassan',
                'grade_level' => 3,
                'school_term' => 2,
                'avatar_path' => 'avatars/amina.png',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Amina Hassan',
            'grade_level' => 3,
            'school_term' => 2,
            'avatar_path' => 'avatars/amina.png',
        ]);
    }

    public function test_deleting_a_child_cascades_related_records(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $activity = Activity::factory()->for($student)->create();
        $contract = RewardContract::factory()->for($student)->create();
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create();

        $this->actingAs($parent)
            ->delete(route('students.destroy', $student))
            ->assertRedirect();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $this->assertDatabaseMissing('reward_contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('parent_materials', [
            'id' => $material->id,
            'student_id' => null,
        ]);
    }

    public function test_parent_cannot_view_another_parents_children(): void
    {
        $parentA = User::factory()->create();
        Student::factory()->for($parentA)->create();
        $parentB = User::factory()->create();
        $childOfB = Student::factory()->for($parentB)->create();

        $this->actingAs($parentA)
            ->get(route('students.show', $childOfB))
            ->assertForbidden();
    }

    public function test_parent_cannot_update_another_parents_children(): void
    {
        $parentA = User::factory()->create();
        Student::factory()->for($parentA)->create();
        $parentB = User::factory()->create();
        $childOfB = Student::factory()->for($parentB)->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($parentA)
            ->patch(route('students.update', $childOfB), [
                'name' => 'Hijacked Name',
                'grade_level' => 1,
                'school_term' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('students', [
            'id' => $childOfB->id,
            'name' => 'Original Name',
            'user_id' => $parentB->id,
        ]);
    }

    public function test_parent_cannot_delete_another_parents_children(): void
    {
        $parentA = User::factory()->create();
        Student::factory()->for($parentA)->create();
        $parentB = User::factory()->create();
        $childOfB = Student::factory()->for($parentB)->create();

        $this->actingAs($parentA)
            ->delete(route('students.destroy', $childOfB))
            ->assertForbidden();

        $this->assertDatabaseHas('students', ['id' => $childOfB->id]);
    }

    public function test_parent_can_view_their_own_child(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('students.show', $student))
            ->assertOk();
    }

    public function test_activity_and_material_enums_are_persisted(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $material = ParentMaterial::factory()->for($parent)->for($student)->create([
            'type' => MaterialType::Exam,
            'status' => MaterialStatus::Pending,
        ]);

        $activity = Activity::factory()->for($student)->for($material)->create([
            'status' => ActivityStatus::Draft,
        ]);

        $this->assertSame(MaterialType::Exam, $material->fresh()->type);
        $this->assertSame(MaterialStatus::Pending, $material->fresh()->status);
        $this->assertSame(ActivityStatus::Draft, $activity->fresh()->status);
    }
}
