<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\DemoPresentationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_demo_presentation_seeder_creates_accounts_and_enriched_analytics(): void
    {
        $this->seed(DemoPresentationSeeder::class);

        $parent = User::query()->where('email', DemoPresentationSeeder::PARENT_EMAIL)->first();
        $studentUser = User::query()->where('email', DemoPresentationSeeder::STUDENT_EMAIL)->first();
        $teacher = User::query()->where('email', DemoPresentationSeeder::TEACHER_EMAIL)->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($studentUser);
        $this->assertNotNull($teacher);
        $this->assertSame(DemoPresentationSeeder::STUDENT_DISPLAY_NAME, $studentUser->name);
        $this->assertSame(UserRole::Admin, $teacher->role);
        $this->assertTrue(Hash::check(DemoPresentationSeeder::PASSWORD, $parent->password));
        $this->assertTrue(Hash::check(DemoPresentationSeeder::PASSWORD, $teacher->password));

        $ahmad = Student::query()
            ->where('user_id', $parent->id)
            ->where('name', DemoPresentationSeeder::STUDENT_DISPLAY_NAME)
            ->first();

        $this->assertNotNull($ahmad);
        $this->assertSame(1, $ahmad->grade_level);
        $this->assertTrue($parent->students->contains(fn (Student $s): bool => $s->is($ahmad)));

        foreach (DemoPresentationSeeder::LESSON_KEYS as $lessonKey) {
            $this->assertTrue(
                LessonAnalytic::query()
                    ->where('student_id', $ahmad->id)
                    ->where('lesson_key', $lessonKey)
                    ->where('event_type', 'lesson_complete')
                    ->exists(),
                "Missing lesson_complete for {$lessonKey}",
            );
        }

        $eventTypes = LessonAnalytic::query()
            ->where('student_id', $ahmad->id)
            ->pluck('event_type')
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach (['voice_attempt', 'trace_attempt', 'quiz_attempt', 'micro_hint', 'station_complete', 'lesson_complete'] as $type) {
            $this->assertContains($type, $eventTypes);
        }

        $parentAnalytics = $this->actingAs($parent)
            ->withSession(['active_student_id' => $ahmad->id])
            ->getJson(route('parent.mastery-analytics.data', $ahmad));

        $parentAnalytics->assertOk()
            ->assertJsonPath('student_id', $ahmad->id);

        $this->assertGreaterThan(0, (int) $parentAnalytics->json('overview.completion_rate'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('overview.average_mastery_score'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('overview.total_time_spent_seconds'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('stations.voice.attempts'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('stations.tracing.stroke_accuracy'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('stations.quiz_discovery.first_attempt_success_rate'));
        $this->assertGreaterThan(0, (int) $parentAnalytics->json('ai_interventions.total_hints'));

        $teacherAnalytics = $this->actingAs($teacher)
            ->getJson(route('admin.mastery-analytics.data'));

        $teacherAnalytics->assertOk();
        $this->assertGreaterThan(0, (int) $teacherAnalytics->json('overview.students_tracked'));
        $this->assertGreaterThan(0, (int) $teacherAnalytics->json('overview.average_mastery_score'));
        $this->assertGreaterThan(0, (int) $teacherAnalytics->json('ai_interventions.total_hints'));
    }

    public function test_demo_presentation_seeder_is_idempotent(): void
    {
        $this->seed(DemoPresentationSeeder::class);
        $this->seed(DemoPresentationSeeder::class);

        $this->assertSame(1, User::query()->where('email', DemoPresentationSeeder::PARENT_EMAIL)->count());
        $this->assertSame(1, User::query()->where('email', DemoPresentationSeeder::STUDENT_EMAIL)->count());
        $this->assertSame(1, User::query()->where('email', DemoPresentationSeeder::TEACHER_EMAIL)->count());
        $this->assertSame(
            1,
            Student::query()
                ->where('name', DemoPresentationSeeder::STUDENT_DISPLAY_NAME)
                ->whereHas('user', fn ($q) => $q->where('email', DemoPresentationSeeder::PARENT_EMAIL))
                ->count(),
        );
    }
}
