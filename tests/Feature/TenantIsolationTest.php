<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InteractiveLesson;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_tenant_seeder_creates_default_school_tenant(): void
    {
        $this->seed(TenantSeeder::class);

        $this->assertDatabaseHas('tenants', [
            'name' => 'مدرسة سنابل النموذجية',
            'slug' => 'sanabel-model-school',
            'status' => 'active',
        ]);
    }

    public function test_queries_under_tenant_a_hide_tenant_b_data(): void
    {
        $tenantA = Tenant::query()->create([
            'name' => 'مدرسة أ',
            'slug' => 'school-a',
            'domain' => 'a.sanabel.test',
            'status' => 'active',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b',
            'domain' => 'b.sanabel.test',
            'status' => 'active',
        ]);

        TenantContext::set($tenantA);
        $userA = User::factory()->create(['email' => 'parent-a@tenant.test']);
        $studentA = Student::factory()->for($userA)->create();
        $materialA = LearningMaterial::factory()->create(['title' => 'مادة أ']);
        $lessonA = InteractiveLesson::query()->create([
            'learning_material_id' => $materialA->id,
            'lesson_key' => 'ar-g1-tenant-a',
            'title' => 'درس أ',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'published',
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $studentA->id,
            'lesson_key' => 'ar-g1-tenant-a',
            'interactive_lesson_id' => $lessonA->id,
            'concept_key' => 'voice',
            'event_type' => 'voice_attempt',
            'error_count' => 1,
            'payload' => ['pronunciation_score' => 90],
        ]);

        $this->assertSame($tenantA->id, $userA->fresh()->tenant_id);
        $this->assertSame($tenantA->id, $lessonA->fresh()->tenant_id);

        TenantContext::set($tenantB);
        $userB = User::factory()->create(['email' => 'parent-b@tenant.test']);
        $studentB = Student::factory()->for($userB)->create();
        $materialB = LearningMaterial::factory()->create(['title' => 'مادة ب']);
        $lessonB = InteractiveLesson::query()->create([
            'learning_material_id' => $materialB->id,
            'lesson_key' => 'ar-g1-tenant-b',
            'title' => 'درس ب',
            'subject_code' => 'AR',
            'grade_level' => 1,
            'station_count' => 6,
            'status' => 'published',
        ]);
        LessonAnalytic::query()->create([
            'student_id' => $studentB->id,
            'lesson_key' => 'ar-g1-tenant-b',
            'interactive_lesson_id' => $lessonB->id,
            'concept_key' => 'voice',
            'event_type' => 'voice_attempt',
            'error_count' => 1,
            'payload' => ['pronunciation_score' => 70],
        ]);

        TenantContext::set($tenantA);

        $this->assertTrue(User::query()->whereKey($userA->id)->exists());
        $this->assertFalse(User::query()->whereKey($userB->id)->exists());
        $this->assertSame(['ar-g1-tenant-a'], InteractiveLesson::query()->pluck('lesson_key')->all());
        $this->assertSame(1, LessonAnalytic::query()->count());
        $this->assertSame(
            $studentA->id,
            LessonAnalytic::query()->value('student_id'),
        );

        TenantContext::set($tenantB);

        $this->assertTrue(User::query()->whereKey($userB->id)->exists());
        $this->assertFalse(User::query()->whereKey($userA->id)->exists());
        $this->assertSame(['ar-g1-tenant-b'], InteractiveLesson::query()->pluck('lesson_key')->all());
        $this->assertSame(1, LessonAnalytic::query()->count());
        $this->assertSame($studentB->id, LessonAnalytic::query()->value('student_id'));
    }

    public function test_without_tenant_context_models_remain_visible_across_tenants(): void
    {
        $tenantA = Tenant::query()->create([
            'name' => 'مدرسة أ',
            'slug' => 'school-a-open',
            'status' => 'active',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b-open',
            'status' => 'active',
        ]);

        TenantContext::set($tenantA);
        User::factory()->create(['email' => 'open-a@tenant.test']);

        TenantContext::set($tenantB);
        User::factory()->create(['email' => 'open-b@tenant.test']);

        TenantContext::clear();

        $this->assertSame(2, User::query()->whereIn('email', [
            'open-a@tenant.test',
            'open-b@tenant.test',
        ])->count());
    }
}
