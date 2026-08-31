<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\Badge;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\User;
use App\Notifications\WeeklyParentEncouragementNotification;
use App\Notifications\WeeklyParentSummaryNotification;
use App\Services\Notifications\WeeklyParentSummaryService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ParentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_weekly_summary_notification_is_sent_with_correct_metrics(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Amina Hassan',
        ]);
        $this->seedWeeklyActivityData($parent, $student);

        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parent);
        $parent->notify(new WeeklyParentSummaryNotification($summary));

        Notification::assertSentTo(
            $parent,
            WeeklyParentSummaryNotification::class,
            function (WeeklyParentSummaryNotification $notification) use ($parent): bool {
                $payload = $notification->toArray($parent);

                return ($payload['children'][0]['activities_completed'] ?? null) === 2
                    && ($payload['children'][0]['xp_earned'] ?? null) === 120
                    && ($payload['children'][0]['weak_topics'][0]['subject'] ?? null) === 'Science';
            },
        );
    }

    public function test_in_app_notification_is_persisted_in_database(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $this->seedWeeklyActivityData($parent, $student);

        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parent);
        $parent->notify(new WeeklyParentSummaryNotification($summary));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $parent->id,
            'type' => WeeklyParentSummaryNotification::class,
        ]);

        $this->assertSame(1, $parent->unreadNotifications()->count());
    }

    public function test_parent_can_view_and_mark_notifications_as_read(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'Amina Hassan',
        ]);
        $this->seedWeeklyActivityData($parent, $student);

        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parent);
        $parent->notify(new WeeklyParentSummaryNotification($summary));

        $notification = $parent->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Weekly Progress Summary')
            ->assertSee('Amina Hassan');

        $this->actingAs($parent)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()?->read_at);
        $this->assertSame(0, $parent->fresh()?->unreadNotifications()->count());
    }

    public function test_scheduled_console_command_dispatches_weekly_summaries_for_active_parents(): void
    {
        Notification::fake();

        $parentWithActivity = User::factory()->create();
        $activeStudent = Student::factory()->for($parentWithActivity)->create();
        $this->seedRecentAttempt($parentWithActivity, $activeStudent, xpEarned: 25);

        $parentWithInactiveStudent = User::factory()->create();
        Student::factory()->for($parentWithInactiveStudent)->create();

        $parentWithoutStudents = User::factory()->create();

        $this->artisan('sanabel:send-weekly-summaries')
            ->assertSuccessful();

        Notification::assertSentTo($parentWithActivity, WeeklyParentSummaryNotification::class);
        Notification::assertSentTo($parentWithInactiveStudent, WeeklyParentEncouragementNotification::class);
        Notification::assertNotSentTo($parentWithoutStudents, WeeklyParentSummaryNotification::class);
        Notification::assertNotSentTo($parentWithoutStudents, WeeklyParentEncouragementNotification::class);
    }

    public function test_weekly_summary_aggregates_metrics_for_multiple_children_in_single_report(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $gradeOneChild = Student::factory()->for($parent)->create([
            'name' => 'Child Grade 1',
            'grade_level' => 1,
        ]);
        $gradeThreeChild = Student::factory()->for($parent)->create([
            'name' => 'Child Grade 3',
            'grade_level' => 3,
        ]);
        $gradeFiveChild = Student::factory()->for($parent)->create([
            'name' => 'Child Grade 5',
            'grade_level' => 5,
        ]);

        $this->seedRecentAttempt($parent, $gradeOneChild, xpEarned: 10, completedDaysAgo: 1);
        $this->seedRecentAttempt($parent, $gradeThreeChild, xpEarned: 20, completedDaysAgo: 2);
        $this->seedRecentAttempt($parent, $gradeFiveChild, xpEarned: 30, completedDaysAgo: 3);

        $this->artisan('sanabel:send-weekly-summaries')->assertSuccessful();

        Notification::assertSentTo(
            $parent,
            WeeklyParentSummaryNotification::class,
            function (WeeklyParentSummaryNotification $notification) use ($parent, $gradeOneChild, $gradeThreeChild, $gradeFiveChild): bool {
                $payload = $notification->toArray($parent);
                $children = collect($payload['children']);

                return $children->count() === 3
                    && $children->contains('student_id', $gradeOneChild->id)
                    && $children->contains('student_id', $gradeThreeChild->id)
                    && $children->contains('student_id', $gradeFiveChild->id)
                    && ($children->firstWhere('student_id', $gradeOneChild->id)['xp_earned'] ?? null) === 10
                    && ($children->firstWhere('student_id', $gradeThreeChild->id)['xp_earned'] ?? null) === 20
                    && ($children->firstWhere('student_id', $gradeFiveChild->id)['xp_earned'] ?? null) === 30
                    && $children->pluck('grade_level')->sort()->values()->all() === [1, 3, 5];
            },
        );

        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parent);
        $html = view('emails.weekly-summary', [
            'parent' => $parent,
            'summary' => $summary,
            'analyticsUrl' => route('parent.analytics'),
        ])->render();

        $this->assertStringContainsString($gradeOneChild->name, $html);
        $this->assertStringContainsString($gradeThreeChild->name, $html);
        $this->assertStringContainsString($gradeFiveChild->name, $html);
        $this->assertStringContainsString((string) $gradeOneChild->grade_level, $html);
        $this->assertStringContainsString((string) $gradeThreeChild->grade_level, $html);
        $this->assertStringContainsString((string) $gradeFiveChild->grade_level, $html);
    }

    public function test_bulk_command_sends_active_and_encouragement_variants_and_skips_childless_parents(): void
    {
        Notification::fake();

        for ($index = 0; $index < 50; $index++) {
            $parent = User::factory()->create();
            $student = Student::factory()->for($parent)->create();
            $this->seedRecentAttempt($parent, $student, xpEarned: 15);
        }

        for ($index = 0; $index < 30; $index++) {
            $parent = User::factory()->create();
            Student::factory()->for($parent)->create();
        }

        User::factory()->count(20)->create();

        $this->artisan('sanabel:send-weekly-summaries')->assertSuccessful();

        Notification::assertSentTimes(WeeklyParentSummaryNotification::class, 50);
        Notification::assertSentTimes(WeeklyParentEncouragementNotification::class, 30);
        Notification::assertCount(80);
    }

    public function test_parent_cannot_mark_another_parents_notification_as_read(): void
    {
        $parentA = User::factory()->create();
        $studentA = Student::factory()->for($parentA)->create();
        $this->seedWeeklyActivityData($parentA, $studentA);

        $parentB = User::factory()->create();

        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parentA);
        $parentA->notify(new WeeklyParentSummaryNotification($summary));

        $notification = $parentA->notifications()->firstOrFail();

        $this->actingAs($parentB)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()?->read_at);
    }

    public function test_mark_all_as_read_uses_single_bulk_update_query(): void
    {
        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $summary = app(WeeklyParentSummaryService::class)->buildForParent($parent);

        for ($index = 0; $index < 55; $index++) {
            $parent->notify(new WeeklyParentSummaryNotification($summary));
        }

        $this->assertSame(55, $parent->unreadNotifications()->count());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($parent)
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $updateQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_contains(strtolower($query['query']), 'update "notifications"'))
            ->values();

        $this->assertCount(1, $updateQueries);
        $this->assertSame(0, $parent->fresh()?->unreadNotifications()->count());
    }

    private function seedRecentAttempt(
        User $parent,
        Student $student,
        int $xpEarned,
        int $completedDaysAgo = 1,
    ): void {
        $material = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Weekly Subject',
                'status' => MaterialStatus::Completed,
            ]);

        $activity = Activity::factory()->for($student)->create([
            'parent_material_id' => $material->id,
            'status' => ActivityStatus::Published,
        ]);

        ActivityAttempt::factory()->for($student)->for($activity)->create([
            'score' => 4,
            'total_questions' => 5,
            'xp_earned' => $xpEarned,
            'completed_at' => now()->subDays($completedDaysAgo),
        ]);
    }

    private function seedWeeklyActivityData(User $parent, Student $student): void
    {
        $mathMaterial = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Math',
                'status' => MaterialStatus::Completed,
            ]);

        $scienceMaterial = ParentMaterial::factory()
            ->for($parent)
            ->for($student)
            ->create([
                'title' => 'Science',
                'status' => MaterialStatus::Completed,
            ]);

        $mathActivity = Activity::factory()->for($student)->create([
            'parent_material_id' => $mathMaterial->id,
            'status' => ActivityStatus::Published,
        ]);

        $scienceActivity = Activity::factory()->for($student)->create([
            'parent_material_id' => $scienceMaterial->id,
            'status' => ActivityStatus::Published,
        ]);

        ActivityAttempt::factory()->for($student)->for($mathActivity)->create([
            'score' => 4,
            'total_questions' => 5,
            'xp_earned' => 50,
            'completed_at' => now()->subDays(2),
        ]);

        ActivityAttempt::factory()->for($student)->for($scienceActivity)->create([
            'score' => 2,
            'total_questions' => 5,
            'xp_earned' => 70,
            'completed_at' => now()->subDays(1),
        ]);

        ActivityAttempt::factory()->for($student)->for($scienceActivity)->create([
            'score' => 2,
            'total_questions' => 5,
            'xp_earned' => 0,
            'completed_at' => now()->subDays(10),
        ]);

        $badge = Badge::query()->where('slug', 'first_activity')->firstOrFail();
        $student->badges()->attach($badge->id, ['unlocked_at' => now()->subDays(3)]);
    }
}
