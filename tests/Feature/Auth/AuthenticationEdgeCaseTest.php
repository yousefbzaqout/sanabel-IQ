<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        User::factory()->create(['email' => 'parent_a@example.com']);

        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Parent A Duplicate',
            'email' => 'PARENT_A@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'taken',
            strtolower(implode(' ', session('errors')->get('email'))),
        );

        $this->assertGuest();
        $this->assertSame(1, User::query()->whereRaw('LOWER(email) = ?', ['parent_a@example.com'])->count());
    }

    public function test_parent_can_authenticate_with_uppercase_email(): void
    {
        $user = User::factory()->create(['email' => 'parent_a@example.com']);

        $this->post(route('login'), [
            'email' => 'PARENT_A@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_script_tags_in_parent_name_are_escaped_in_views(): void
    {
        $payload = "<script>alert('xss')</script>";
        $parent = User::factory()->create(['name' => $payload]);
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false);

        $this->actingAs($parent)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee($payload, false);
    }

    public function test_guest_cannot_open_child_creation_url(): void
    {
        $this->get(route('students.create'))->assertRedirect(route('login'));
    }
}
