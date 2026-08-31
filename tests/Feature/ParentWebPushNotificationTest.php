<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ParentGoalStatus;
use App\Models\Activity;
use App\Models\ParentLearningGoal;
use App\Models\Student;
use App\Models\User;
use App\Notifications\GoalAchievedWebPushNotification;
use App\Notifications\WeeklySummaryWebPushNotification;
use App\Services\Gameplay\ActivitySubmissionService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class ParentWebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    public function test_parent_can_store_push_subscription(): void
    {
        $parent = User::factory()->create();

        $payload = $this->sampleSubscriptionPayload();

        $this->actingAs($parent)
            ->postJson(route('parent.push-subscriptions.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $parent->id,
            'endpoint' => $payload['endpoint'],
            'public_key' => $payload['keys']['p256dh'],
            'auth_token' => $payload['keys']['auth'],
        ]);
    }

    public function test_parent_can_delete_push_subscription(): void
    {
        $parent = User::factory()->create();
        $payload = $this->sampleSubscriptionPayload();

        $parent->updatePushSubscription(
            $payload['endpoint'],
            $payload['keys']['p256dh'],
            $payload['keys']['auth'],
        );

        $this->actingAs($parent)
            ->deleteJson(route('parent.push-subscriptions.destroy'), [
                'endpoint' => $payload['endpoint'],
            ])
            ->assertNoContent();

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
        ]);
    }

    public function test_push_notification_is_dispatched_on_goal_achievement(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create(['total_xp' => 0]);
        $subscription = $this->sampleSubscriptionPayload();

        $parent->updatePushSubscription(
            $subscription['endpoint'],
            $subscription['keys']['p256dh'],
            $subscription['keys']['auth'],
        );

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
            'payload' => $this->sampleActivityPayload(),
        ]);

        app(ActivitySubmissionService::class)->submit(
            $activity,
            $student,
            $this->allCorrectAnswers(),
        );

        Notification::assertSentTo(
            $parent,
            GoalAchievedWebPushNotification::class,
            fn (GoalAchievedWebPushNotification $notification, array $channels): bool => in_array(WebPushChannel::class, $channels, true),
        );
    }

    public function test_weekly_summary_web_push_is_dispatched_when_parent_has_subscription(): void
    {
        Notification::fake();

        $parent = User::factory()->create();
        $student = Student::factory()->for($parent)->create();
        $subscription = $this->sampleSubscriptionPayload();

        $parent->updatePushSubscription(
            $subscription['endpoint'],
            $subscription['keys']['p256dh'],
            $subscription['keys']['auth'],
        );

        $summary = [
            'period_start' => now()->subWeek()->toDateString(),
            'period_end' => now()->toDateString(),
            'children' => [
                [
                    'name' => $student->name,
                    'activities_completed' => 3,
                    'xp_earned' => 90,
                ],
            ],
        ];

        $parent->notify(new WeeklySummaryWebPushNotification($summary));

        Notification::assertSentTo(
            $parent,
            WeeklySummaryWebPushNotification::class,
            fn (WeeklySummaryWebPushNotification $notification, array $channels): bool => in_array(WebPushChannel::class, $channels, true),
        );
    }

    public function test_parent_cannot_modify_another_parents_push_subscription(): void
    {
        $parentA = User::factory()->create();
        $parentB = User::factory()->create();
        $payload = $this->sampleSubscriptionPayload();

        $parentA->updatePushSubscription(
            $payload['endpoint'],
            $payload['keys']['p256dh'],
            $payload['keys']['auth'],
        );

        $this->actingAs($parentB)
            ->deleteJson(route('parent.push-subscriptions.destroy'), [
                'endpoint' => $payload['endpoint'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'subscribable_id' => $parentA->id,
        ]);
    }

    /**
     * @return array{endpoint: string, keys: array{p256dh: string, auth: string}}
     */
    private function sampleSubscriptionPayload(): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.str_repeat('a', 32),
            'keys' => [
                'p256dh' => 'BNcRdmlATbH5it4zK8qBfa0s0A64asjdksfhaskjhf1234567890AB',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
        ];
    }

    /**
     * @return array{questions: list<array<string, mixed>>}
     */
    private function sampleActivityPayload(): array
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
