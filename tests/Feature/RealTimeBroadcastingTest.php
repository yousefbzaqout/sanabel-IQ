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
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
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
