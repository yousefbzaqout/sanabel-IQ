<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ParentGoalStatus;
use App\Events\ActivityCompletedBroadcastEvent;
use App\Events\GoalAchievedBroadcastEvent;
use App\Models\Activity;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use App\Models\User;
use App\Notifications\GoalAchievedNotification;
use App\Services\Gameplay\ActivitySubmissionService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RealTimeBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_activity_completion_dispatches_realtime_broadcast_event_to_parent_channel(): void
    {
        Event::fake([ActivityCompletedBroadcastEvent::class]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'سارة',
            'total_xp' => 0,
        ]);

        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'title' => 'اختبار الرياضيات',
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertOk();

        Event::assertDispatched(ActivityCompletedBroadcastEvent::class, function (ActivityCompletedBroadcastEvent $event) use ($parent, $student, $activity): bool {
            $channels = $event->broadcastOn();

            $this->assertCount(1, $channels);
            $this->assertSame('private-parent.'.$parent->id, $channels[0]->name);

            $payload = $event->broadcastWith();

            $this->assertSame($student->name, $payload['child_name']);
            $this->assertSame($activity->title, $payload['activity_title']);
            $this->assertSame(100, $payload['score_percent']);
            $this->assertSame(50, $payload['xp_earned']);

            return true;
        });
    }

    public function test_goal_achievement_dispatches_realtime_goal_event(): void
    {
        Event::fake([GoalAchievedBroadcastEvent::class]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create([
            'name' => 'أحمد',
            'total_xp' => 0,
        ]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activity = Activity::factory()
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertOk();

        Event::assertDispatched(GoalAchievedBroadcastEvent::class, function (GoalAchievedBroadcastEvent $event) use ($parent, $student): bool {
            $channels = $event->broadcastOn();

            $this->assertCount(1, $channels);
            $this->assertSame('private-parent.'.$parent->id, $channels[0]->name);

            $payload = $event->broadcastWith();

            $this->assertSame($student->name, $payload['child_name']);
            $this->assertSame(1, $payload['target_activity_count']);
            $this->assertSame(50, $payload['target_xp']);
            $this->assertNotEmpty($payload['achieved_at']);

            return true;
        });
    }

    public function test_parent_cannot_listen_to_another_parents_private_channel(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();

        $this->actingAs($parentB);

        $channels = Broadcast::getChannels();
        $callback = $channels->get('parent.{id}');

        $this->assertNotNull($callback);
        $this->assertIsCallable($callback);

        $this->assertFalse($callback($parentB, (string) $parentA->id));
        $this->assertTrue($callback($parentB, (string) $parentB->id));
        $this->assertSame($parentB->id, Auth::id());
    }

    public function test_unauthorized_parent_receives_forbidden_when_subscribing_to_another_parents_channel(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();

        $this->actingAs($parentB)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-parent.'.$parentA->id,
            ])
            ->assertForbidden();
    }

    public function test_authenticated_parent_can_authorize_own_private_channel(): void
    {
        $parent = User::factory()->create();

        $this->actingAs($parent)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-parent.'.$parent->id,
            ])
            ->assertOk();
    }

    public function test_guest_cannot_authorize_private_parent_channel(): void
    {
        $parent = User::factory()->create();

        $response = $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-parent.'.$parent->id,
        ]);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_activity_completed_broadcast_payload_contains_only_explicit_safe_keys(): void
    {
        $parent = User::factory()->create(['password' => bcrypt('secret-password')]);
        $student = Student::factory()->for($parent)->create(['name' => 'Leak Test Child']);
        $activity = Activity::factory()->for($student)->create([
            'title' => 'Safe Activity',
            'status' => ActivityStatus::Published,
            'xp_reward' => 25,
            'payload' => $this->samplePayload(),
        ]);

        $event = new ActivityCompletedBroadcastEvent(
            parentId: (int) $parent->id,
            childName: $student->name,
            activityTitle: $activity->title,
            scorePercent: 100,
            xpEarned: 25,
        );

        $payload = $event->broadcastWith();

        $this->assertSame(
            ['child_name', 'activity_title', 'score_percent', 'xp_earned'],
            array_keys($payload),
        );
        $this->assertStringNotContainsString('secret-password', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('password', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('goal', $payload);
        $this->assertArrayNotHasKey('user', $payload);
    }

    public function test_goal_achieved_broadcast_payload_contains_only_explicit_safe_keys(): void
    {
        $parent = User::factory()->create(['password' => bcrypt('secret-password')]);
        $student = Student::factory()->for($parent)->create(['name' => 'Goal Child']);
        $goal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 2,
            'target_xp' => 80,
        ]);

        $event = new GoalAchievedBroadcastEvent(
            parentId: (int) $goal->parent_id,
            childName: $student->name,
            targetActivityCount: (int) $goal->target_activity_count,
            targetXp: (int) $goal->target_xp,
            subject: null,
            achievedAt: now()->toIso8601String(),
        );

        $payload = $event->broadcastWith();

        $this->assertSame(
            ['child_name', 'target_activity_count', 'target_xp', 'subject', 'achieved_at'],
            array_keys($payload),
        );
        $this->assertStringNotContainsString('secret-password', json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('goal', $payload);
        $this->assertArrayNotHasKey('student', $payload);
        $this->assertArrayNotHasKey('parent', $payload);
    }

    public function test_null_broadcast_driver_does_not_block_activity_submission_or_goal_achievement(): void
    {
        config(['broadcasting.default' => 'null']);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activity = Activity::factory()->for($student)->create([
            'status' => ActivityStatus::Published,
            'xp_reward' => 50,
            'payload' => $this->samplePayload(),
        ]);

        $this->actingAs($parent)
            ->withSession(['active_student_id' => $student->id])
            ->postJson(route('student.activities.submit', $activity), [
                'answers' => $this->allCorrectAnswers(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_attempts', [
            'activity_id' => $activity->id,
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('parent_learning_goals', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => ParentGoalStatus::Achieved->value,
        ]);
    }

    public function test_log_broadcast_driver_does_not_block_activity_submission_or_goal_achievement(): void
    {
        config(['broadcasting.default' => 'log']);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 1,
            'target_xp' => 50,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activity = Activity::factory()->for($student)->create([
            'status' => ActivityStatus::Published,
            'xp_reward' => 50,
            'payload' => $this->samplePayload(),
        ]);

        app(ActivitySubmissionService::class)->submit(
            $activity,
            $student,
            $this->allCorrectAnswers(),
        );

        $this->assertSame(ParentGoalStatus::Achieved, ParentLearningGoal::query()->first()?->status);
        $this->assertSame(50, $student->fresh()?->total_xp);
    }

    public function test_rapid_activity_completions_dispatch_all_broadcast_events_without_duplicate_goal_notifications(): void
    {
        Notification::fake();
        Event::fake([ActivityCompletedBroadcastEvent::class, GoalAchievedBroadcastEvent::class]);

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);

        ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 10,
            'target_xp' => 500,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => ParentGoalStatus::Pending,
        ]);

        $activities = Activity::factory()
            ->count(10)
            ->for($student)
            ->create([
                'status' => ActivityStatus::Published,
                'xp_reward' => 50,
                'payload' => $this->samplePayload(),
            ]);

        $service = app(ActivitySubmissionService::class);

        foreach ($activities as $activity) {
            $service->submit($activity, $student, $this->allCorrectAnswers());
        }

        Event::assertDispatchedTimes(ActivityCompletedBroadcastEvent::class, 10);
        Event::assertDispatchedTimes(GoalAchievedBroadcastEvent::class, 1);
        Notification::assertSentToTimes($parent, GoalAchievedNotification::class, 1);
    }

    /**
     * @return array{questions: list<array<string, mixed>>}
     */
    private function samplePayload(): array
    {
        return [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'question' => '2 + 2 = ?',
                    'options' => ['3', '4', '5'],
                    'correct_index' => 1,
                    'explanation' => 'Answer is 4.',
                ],
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function allCorrectAnswers(): array
    {
        return [1];
    }
}
