<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\InteractiveLessons\Pages\ListInteractiveLessons;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_parent_cannot_view_another_childs_mastery_analytics(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $student = Student::factory()->for($owner)->create();
        Student::factory()->for($other)->create();

        $this->actingAs($other)
            ->withSession(['active_student_id' => Student::query()->where('user_id', $other->id)->value('id')])
            ->get(route('parent.mastery-analytics.show', $student))
            ->assertForbidden();

        $this->actingAs($other)
            ->getJson(route('parent.mastery-analytics.data', $student))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_parent_mastery_analytics(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->get(route('parent.mastery-analytics.show', $student))
            ->assertRedirect(route('login'));

        $this->getJson(route('parent.mastery-analytics.data', $student))
            ->assertUnauthorized();
    }

    public function test_parent_can_view_own_child_mastery_analytics(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('parent.mastery-analytics.show', $student))
            ->assertOk();

        $this->actingAs($parent)
            ->getJson(route('parent.mastery-analytics.data', $student))
            ->assertOk()
            ->assertJsonPath('student_id', $student->id);
    }

    public function test_non_admin_cannot_access_teacher_mastery_analytics(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get(route('admin.mastery-analytics.show'))
            ->assertForbidden();

        $this->actingAs($parent)
            ->getJson(route('admin.mastery-analytics.data'))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_teacher_mastery_analytics(): void
    {
        $this->get(route('admin.mastery-analytics.show'))
            ->assertRedirect(route('login'));

        $this->getJson(route('admin.mastery-analytics.data'))
            ->assertUnauthorized();
    }

    public function test_admin_can_access_teacher_mastery_analytics(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.mastery-analytics.show'))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('admin.mastery-analytics.data'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_filament_interactive_lessons(): void
    {
        $parent = User::factory()->create();
        Student::factory()->for($parent)->create();

        $this->actingAs($parent)
            ->get('/admin/interactive-lessons')
            ->assertForbidden();

        Livewire::actingAs($parent)
            ->test(ListInteractiveLessons::class)
            ->assertForbidden();
    }

    public function test_guest_cannot_access_filament_interactive_lessons(): void
    {
        $this->get('/admin/interactive-lessons')
            ->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_call_ai_scoring_endpoints(): void
    {
        $this->postJson(route('student.ai.pronunciation'), [
            'target' => 'رَ',
            'transcript' => 'رَ',
        ])->assertUnauthorized();

        $this->postJson(route('student.ai.stroke'), [
            'points' => [
                ['x' => 10, 'y' => 10],
                ['x' => 12, 'y' => 20],
                ['x' => 14, 'y' => 30],
                ['x' => 16, 'y' => 40],
            ],
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_without_active_student_cannot_call_ai_endpoints(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => null])
            ->postJson(route('student.ai.pronunciation'), [
                'target' => 'رَ',
                'transcript' => 'رَ',
            ])
            ->assertForbidden();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => null])
            ->postJson(route('student.ai.stroke'), [
                'points' => [
                    ['x' => 10, 'y' => 10],
                    ['x' => 12, 'y' => 20],
                    ['x' => 14, 'y' => 30],
                    ['x' => 16, 'y' => 40],
                ],
            ])
            ->assertForbidden();
    }

    public function test_parent_cannot_post_lesson_analytics_for_foreign_context_without_active_child(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->withSession(['active_student_id' => null])
            ->postJson(route('student.interactive-lesson.analytics.store', 'ar-g1-letter-raa'), [
                'event_type' => 'voice_attempt',
                'concept_key' => 'diacritic_confusion',
                'station' => 1,
                'payload' => ['pronunciation_score' => 90],
            ])
            ->assertForbidden();
    }
}
