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
use App\Notifications\WeeklyParentSummaryNotification;
use App\Services\Notifications\WeeklyParentSummaryService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $parentWithStudents = User::factory()->create();
        Student::factory()->for($parentWithStudents)->create();

        $parentWithoutStudents = User::factory()->create();

        $this->artisan('sanabel:send-weekly-summaries')
            ->assertSuccessful();

        Notification::assertSentTo($parentWithStudents, WeeklyParentSummaryNotification::class);
        Notification::assertNotSentTo($parentWithoutStudents, WeeklyParentSummaryNotification::class);
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
