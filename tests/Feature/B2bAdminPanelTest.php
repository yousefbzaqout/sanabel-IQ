<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\InteractiveLessons\Pages\EditInteractiveLesson;
use App\Filament\Resources\InteractiveLessons\Pages\ListInteractiveLessons;
use App\Filament\Resources\LessonAnalytics\Pages\ListLessonAnalytics;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Widgets\TenantOverviewStatsWidget;
use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class B2bAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_tenant_admin_cannot_list_users_from_another_school(): void
    {
        [$tenantA, $tenantB, $adminA, $userB] = $this->seedTwoSchools();

        Livewire::actingAs($adminA)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([
                User::query()->withoutTenantScope()->where('tenant_id', $tenantA->id)->whereKey($adminA->id)->firstOrFail(),
            ])
            ->assertCanNotSeeTableRecords([$userB]);

        $this->assertSame($tenantA->id, TenantContext::id());
        unset($tenantB);
    }

    public function test_tenant_admin_cannot_edit_user_from_another_school(): void
    {
        [, , $adminA, $userB] = $this->seedTwoSchools();

        Livewire::actingAs($adminA)
            ->test(EditUser::class, ['record' => $userB->getRouteKey()])
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_see_or_edit_foreign_lesson_analytics(): void
    {
        [$tenantA, $tenantB, $adminA] = $this->seedTwoSchools();

        $analyticA = $this->seedAnalyticForTenant($tenantA, 'ar-g1-school-a');
        $analyticB = $this->seedAnalyticForTenant($tenantB, 'ar-g1-school-b');

        Livewire::actingAs($adminA)
            ->test(ListLessonAnalytics::class)
            ->assertCanSeeTableRecords([$analyticA->fresh()])
            ->assertCanNotSeeTableRecords([$analyticB]);
    }

    public function test_tenant_admin_cannot_edit_interactive_lesson_from_another_school(): void
    {
        [$tenantA, $tenantB, $adminA] = $this->seedTwoSchools();

        $lessonA = $this->seedLessonForTenant($tenantA, 'ar-g1-school-a-lesson');
        $lessonB = $this->seedLessonForTenant($tenantB, 'ar-g1-school-b-lesson');

        Livewire::actingAs($adminA)
            ->test(ListInteractiveLessons::class)
            ->assertCanSeeTableRecords([$lessonA])
            ->assertCanNotSeeTableRecords([$lessonB]);

        Livewire::actingAs($adminA)
            ->test(EditInteractiveLesson::class, ['record' => $lessonB->getRouteKey()])
            ->assertForbidden();
    }

    public function test_super_admin_can_see_users_across_tenants(): void
    {
        [$tenantA, $tenantB, $adminA, $userB] = $this->seedTwoSchools();
        $super = User::factory()->admin()->create(['email' => 'super@sanabel.test']);

        Livewire::actingAs($super)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$adminA->fresh(), $userB->fresh()]);

        $this->assertNull(TenantContext::id());
        unset($tenantA, $tenantB);
    }

    public function test_tenant_dashboard_widget_counts_only_active_tenant(): void
    {
        [$tenantA, $tenantB, $adminA] = $this->seedTwoSchools();

        Student::factory()->for(
            User::factory()->create(['tenant_id' => $tenantA->id, 'email' => 'p2@a.test']),
        )->create();
        Student::factory()->for(
            User::factory()->create(['tenant_id' => $tenantB->id, 'email' => 'p2@b.test']),
        )->create();

        $this->seedLessonForTenant($tenantA, 'ar-g1-widget-a');
        $this->seedLessonForTenant($tenantB, 'ar-g1-widget-b');
        $this->seedAnalyticForTenant($tenantA, 'ar-g1-widget-a');
        $this->seedAnalyticForTenant($tenantB, 'ar-g1-widget-b');

        TenantContext::setTenant($tenantA);
        $this->actingAs($adminA);

        $widget = app(TenantOverviewStatsWidget::class);
        $stats = $widget->getStatsForTesting();

        $this->assertSame(2, $stats['students']);
        $this->assertSame(1, $stats['active_lessons']);
        $this->assertSame(1, $stats['analytics_events']);
    }

    /**
     * @return array{0: Tenant, 1: Tenant, 2: User, 3: User}
     */
    private function seedTwoSchools(): array
    {
        $tenantA = Tenant::query()->create([
            'name' => 'مدرسة أ',
            'slug' => 'school-a',
            'domain' => 'school-a.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b',
            'domain' => 'school-b.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $adminA = User::factory()->tenantAdmin()->create([
            'email' => 'admin@school-a.test',
            'tenant_id' => $tenantA->id,
            'name' => 'مدير أ',
        ]);

        $userB = User::factory()->create([
            'email' => 'parent@school-b.test',
            'tenant_id' => $tenantB->id,
            'name' => 'ولي أمر ب',
        ]);

        return [$tenantA, $tenantB, $adminA, $userB];
    }

    private function seedLessonForTenant(Tenant $tenant, string $lessonKey): InteractiveLesson
    {
        $material = LearningMaterial::factory()->create([
            'title' => 'مادة '.$lessonKey,
        ]);

        return InteractiveLesson::query()->create([
            'tenant_id' => $tenant->id,
            'learning_material_id' => $material->id,
            'lesson_key' => $lessonKey,
            'title' => 'درس '.$lessonKey,
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'published',
        ]);
    }

    private function seedAnalyticForTenant(Tenant $tenant, string $lessonKey): LessonAnalytic
    {
        $parent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'analytics-'.$lessonKey.'@test',
        ]);
        $student = Student::factory()->for($parent)->create();
        $lesson = InteractiveLesson::query()
            ->withoutTenantScope()
            ->where('lesson_key', $lessonKey)
            ->first() ?? $this->seedLessonForTenant($tenant, $lessonKey);

        return LessonAnalytic::query()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'lesson_key' => $lessonKey,
            'interactive_lesson_id' => $lesson->id,
            'concept_key' => 'voice',
            'event_type' => 'voice_attempt',
            'error_count' => 1,
            'payload' => ['pronunciation_score' => 80],
        ]);
    }
}
