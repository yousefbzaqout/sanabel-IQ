<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ParentLearningGoal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoalAchievedBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ParentLearningGoal $goal,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('parent.'.$this->goal->parent_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'goal.achieved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->goal->loadMissing(['student', 'subject']);

        return [
            'child_name' => $this->goal->student->name,
            'target_activity_count' => $this->goal->target_activity_count,
            'target_xp' => $this->goal->target_xp,
            'subject' => $this->goal->subject?->name,
            'achieved_at' => now()->toIso8601String(),
        ];
    }
}
