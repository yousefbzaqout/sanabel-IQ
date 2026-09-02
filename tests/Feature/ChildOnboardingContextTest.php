<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildOnboardingContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_with_no_children_is_redirected_to_child_onboarding(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.child'));
    }

    public function test_parent_can_complete_child_onboarding_wizard(): void
    {
        $parent = User::factory()->create();

        $response = $this->actingAs($parent)
            ->post(route('onboarding.child.store'), [
                'name' => 'Amina',
                'grade_level' => 3,
                'school_term' => 1,
                'avatar_path' => 'avatars/amina.png',
            ]);

        $student = Student::query()->where('user_id', $parent->id)->first();

        $this->assertNotNull($student);
        $this->assertSame('Amina', $student->name);
        $this->assertSame(3, $student->grade_level);
        $this->assertSame(1, $student->school_term);
        $this->assertSame('avatars/amina.png', $student->avatar_path);
        $this->assertSame($student->id, session('active_student_id'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_parent_can_switch_active_child_context(): void
    {
        $parent = User::factory()->create();
        $first = Student::factory()->for($parent)->create(['name' => 'Amina']);
        $second = Student::factory()->for($parent)->create(['name' => 'Omar']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $first->id])
            ->post(route('students.select', $second))
            ->assertRedirect();

        $this->assertSame($second->id, session('active_student_id'));
    }

    public function test_parent_cannot_select_another_parents_child_as_active(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $childA = Student::factory()->for($parentA)->create();
        $childB = Student::factory()->for($parentB)->create();

        $this->actingAs($parentA)
            ->withSession(['active_student_id' => $childA->id])
            ->post(route('students.select', $childB))
            ->assertForbidden();

        $this->assertSame($childA->id, session('active_student_id'));
    }

    public function test_active_child_middleware_ensures_valid_child_selected(): void
    {
        $parent = User::factory()->create();
        $first = Student::factory()->for($parent)->create();
        Student::factory()->for($parent)->create();
        $foreignChild = Student::factory()->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => null])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($first->id, session('active_student_id'));

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $foreignChild->id])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($first->id, session('active_student_id'));
    }

    public function test_onboarding_page_redirects_when_parent_already_has_children(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('onboarding.child'))
            ->assertRedirect(route('students.create'));
    }

    public function test_child_name_is_normalized_before_persistence(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->post(route('onboarding.child.store'), [
                'name' => "  \xE2\x80\x8Fليان  ",
                'grade_level' => 2,
                'school_term' => 1,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('students', [
            'user_id' => $parent->id,
            'name' => 'ليان',
            'grade_level' => 2,
        ]);

        $this->actingAs($parent)
            ->post(route('students.store'), [
                'name' => 'سارةSara Audit',
                'grade_level' => 4,
                'school_term' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'user_id' => $parent->id,
            'name' => 'سارة Audit',
            'grade_level' => 4,
        ]);
    }
}
