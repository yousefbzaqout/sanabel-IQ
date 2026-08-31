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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\ReportHandler;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
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

    public function test_duplicate_endpoint_registration_updates_existing_subscription_keys(): void
    {
        $parent = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('b', 32);

        $this->actingAs($parent)
            ->postJson(route('parent.push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'original-public-key-value-aaaaaaaaaaaa',
                    'auth' => 'original-auth-token-aaa',
                ],
            ])
            ->assertCreated();

        $this->actingAs($parent)
            ->postJson(route('parent.push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'rotated-public-key-value-bbbbbbbbbbbb',
                    'auth' => 'rotated-auth-token-bbb',
                ],
            ])
            ->assertSuccessful();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $parent->id,
            'endpoint' => $endpoint,
            'public_key' => 'rotated-public-key-value-bbbbbbbbbbbb',
            'auth_token' => 'rotated-auth-token-bbb',
        ]);
    }

    public function test_expired_push_endpoint_is_deleted_after_410_gone_report(): void
    {
        $parent = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('c', 32);

        $parent->updatePushSubscription($endpoint, 'p256dh-key', 'auth-token');
        $subscription = PushSubscription::findByEndpoint($endpoint);
        $this->assertNotNull($subscription);

        $report = new MessageSentReport(
            new Request('POST', $endpoint),
            new Response(410, [], 'Gone'),
            false,
            'Gone',
        );

        app(ReportHandler::class)->handleReport(
            $report,
            $subscription,
            (new WebPushMessage)->title('test')->body('test'),
        );

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_expired_push_endpoint_is_deleted_after_404_not_found_report(): void
    {
        $parent = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('d', 32);

        $parent->updatePushSubscription($endpoint, 'p256dh-key', 'auth-token');
        $subscription = PushSubscription::findByEndpoint($endpoint);
        $this->assertNotNull($subscription);

        $report = new MessageSentReport(
            new Request('POST', $endpoint),
            new Response(404, [], 'Not Found'),
            false,
            'Not Found',
        );

        app(ReportHandler::class)->handleReport(
            $report,
            $subscription,
            (new WebPushMessage)->title('test')->body('test'),
        );

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_goal_achieved_web_push_payload_stays_under_browser_size_limit_with_arabic_content(): void
    {
        $parent = User::factory()->create();
        $longArabicName = 'مادة الرياضيات - المستوى الخامس (الوحدة الأولى) '.str_repeat('أ', 200);
        $longArabicName = mb_substr($longArabicName, 0, 255);
        $student = Student::factory()->for($parent)->create([
            'name' => $longArabicName,
        ]);
        $goal = ParentLearningGoal::factory()->for($parent, 'parent')->for($student)->create([
            'target_activity_count' => 5,
            'target_xp' => 200,
        ]);

        $notification = new GoalAchievedWebPushNotification($goal->fresh(['student', 'subject']));
        $payload = $notification->toWebPush($parent, $notification)->toArray();
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertLessThan(4096, strlen($encoded));
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('body', $payload);
        $this->assertArrayHasKey('icon', $payload);
        $this->assertArrayHasKey('badge', $payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertSame(GoalAchievedWebPushNotification::MAX_BODY_LENGTH, mb_strlen((string) $payload['body']));
        $this->assertStringContainsString('مادة الرياضيات', (string) $payload['body']);
        $this->assertStringEndsWith('…', (string) $payload['body']);
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
