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

    public function test_guest_cannot_access_child_onboarding(): void
    {
        $this->get(route('onboarding.child'))->assertRedirect(route('login'));
        $this->post(route('onboarding.child.store'), [
            'name' => 'Omar',
            'grade_level' => 3,
            'school_term' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_onboarding_wizard_rejects_empty_name_and_shows_error(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('onboarding.child'))
            ->followingRedirects()
            ->post(route('onboarding.child.store'), [
                'name' => '',
                'grade_level' => 3,
                'school_term' => 1,
            ])
            ->assertOk()
            ->assertSee(__('validation.required', ['attribute' => 'name']));

        $this->assertDatabaseCount('students', 0);
    }

    public function test_onboarding_validation_errors_survive_when_previous_url_is_dashboard(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('dashboard'))
            ->followingRedirects()
            ->post(route('onboarding.child.store'), [
                'name' => '',
                'grade_level' => 3,
                'school_term' => 1,
            ])
            ->assertOk()
            ->assertSee(__('validation.required', ['attribute' => 'name']))
            ->assertSee(__('Set up your first child'));

        $this->assertDatabaseCount('students', 0);
    }

    public function test_onboarding_wizard_rejects_out_of_range_grade_and_term(): void
    {
        $parent = User::factory()->create();

        foreach ([0, 8] as $grade) {
            $this->actingAs($parent)
                ->from(route('onboarding.child'))
                ->post(route('onboarding.child.store'), [
                    'name' => 'Omar',
                    'grade_level' => $grade,
                    'school_term' => 1,
                ])
                ->assertRedirect(route('onboarding.child'))
                ->assertSessionHasErrors('grade_level');
        }

        $this->actingAs($parent)
            ->from(route('onboarding.child'))
            ->followingRedirects()
            ->post(route('onboarding.child.store'), [
                'name' => 'Omar',
                'grade_level' => 3,
                'school_term' => 3,
            ])
            ->assertOk()
            ->assertSee(__('validation.in', ['attribute' => 'school term']));

        $this->assertDatabaseCount('students', 0);
    }

    public function test_completed_wizard_shows_active_child_in_navigation(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->post(route('onboarding.child.store'), [
                'name' => 'Omar',
                'grade_level' => 3,
                'school_term' => 1,
            ])
            ->assertRedirect(route('dashboard'));

        $omar = Student::query()->where('user_id', $parent->id)->first();
        $this->assertNotNull($omar);
        $this->assertSame($omar->id, session('active_student_id'));

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Omar')
            ->assertSee(__('Grade').' 3')
            ->assertSee(__('Active child').': Omar');
    }

    public function test_parent_can_add_second_child_and_switch_active_context(): void
    {
        $parent = User::factory()->create();
        $omar = Student::factory()->for($parent)->create([
            'name' => 'Omar',
            'grade_level' => 3,
            'school_term' => 1,
        ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $omar->id])
            ->post(route('students.store'), [
                'name' => 'Lina',
                'grade_level' => 1,
                'school_term' => 2,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $lina = Student::query()->where('user_id', $parent->id)->where('name', 'Lina')->first();
        $this->assertNotNull($lina);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $omar->id])
            ->from(route('dashboard'))
            ->post(route('students.select', $lina))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($lina->id, session('active_student_id'));

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lina')
            ->assertSee(__('Grade').' 1');
    }

    public function test_parent_cannot_select_another_parents_named_child_as_active(): void
    {
        $parentA = User::factory()->create(['email' => 'parent_a_context@example.com']);
        $parentB = User::factory()->create(['email' => 'parent_b@example.com']);
        $omar = Student::factory()->for($parentA)->create(['name' => 'Omar']);
        $sami = Student::factory()->for($parentB)->create(['name' => 'Sami']);

        $this->actingAs($parentA)
            ->withSession(['active_student_id' => $omar->id])
            ->post(route('students.select', $sami))
            ->assertForbidden();

        $this->assertSame($omar->id, session('active_student_id'));
        $this->assertDatabaseHas('students', [
            'id' => $sami->id,
            'user_id' => $parentB->id,
            'name' => 'Sami',
        ]);
    }

    public function test_deleting_active_child_falls_back_to_first_remaining_child(): void
    {
        $parent = User::factory()->create();
        $omar = Student::factory()->for($parent)->create(['name' => 'Omar']);
        $lina = Student::factory()->for($parent)->create(['name' => 'Lina']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $lina->id])
            ->delete(route('students.destroy', $lina))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $lina->id]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $lina->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Omar')
            ->assertDontSee('Lina');

        $this->assertSame($omar->id, session('active_student_id'));
    }

    public function test_deleting_last_child_redirects_to_onboarding(): void
    {
        $parent = User::factory()->create();
        $omar = Student::factory()->for($parent)->create(['name' => 'Omar']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $omar->id])
            ->delete(route('students.destroy', $omar));

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.child'));
    }

    public function test_onboarding_cannot_assign_child_to_another_parent(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();

        $this->actingAs($parentA)
            ->post(route('onboarding.child.store'), [
                'name' => 'Hijack',
                'grade_level' => 1,
                'school_term' => 1,
                'user_id' => $parentB->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('students', [
            'name' => 'Hijack',
            'user_id' => $parentA->id,
        ]);
        $this->assertDatabaseMissing('students', [
            'name' => 'Hijack',
            'user_id' => $parentB->id,
        ]);
    }

    public function test_script_tags_in_child_name_are_escaped_on_dashboard(): void
    {
        $payload = "<script>alert('xss')</script>";
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create(['name' => $payload, 'grade_level' => 2]);

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false);
    }

    public function test_non_numeric_active_student_session_falls_back_without_error(): void
    {
        $parent = User::factory()->create();
        $omar = Student::factory()->for($parent)->create(['name' => 'Omar']);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => 'not-an-id'])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($omar->id, session('active_student_id'));

        $this->actingAs($parent)
            ->withSession(['active_student_id' => [999]])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($omar->id, session('active_student_id'));
    }
}
