<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_parent_can_open_child_creation_form(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="grade_level"', false)
            ->assertSee('name="school_term"', false);
    }

    public function test_grade_level_zero_is_rejected(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'name' => 'Out Of Range',
                'grade_level' => 0,
                'school_term' => 1,
            ])
            ->assertSessionHasErrors('grade_level');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_grade_level_seven_is_rejected(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'name' => 'Out Of Range',
                'grade_level' => 7,
                'school_term' => 1,
            ])
            ->assertSessionHasErrors('grade_level');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_school_term_three_is_rejected(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'name' => 'Wrong Term',
                'grade_level' => 1,
                'school_term' => 3,
            ])
            ->assertSessionHasErrors('school_term');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_name_longer_than_255_characters_is_rejected(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'name' => str_repeat('A', 256),
                'grade_level' => 1,
                'school_term' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_script_tags_in_child_name_are_escaped_on_show_page(): void
    {
        $payload = "<script>alert('xss')</script>";
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['name' => $payload]);

        $this->actingAs($parent)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false);
    }
}
