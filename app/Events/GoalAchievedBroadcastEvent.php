<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class GoalAchievedBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int $parentId,
        public readonly string $childName,
        public readonly int $targetActivityCount,
        public readonly int $targetXp,
        public readonly ?string $subject,
        public readonly string $achievedAt,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('parent.'.$this->parentId),
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
        return [
            'child_name' => $this->childName,
            'target_activity_count' => $this->targetActivityCount,
            'target_xp' => $this->targetXp,
            'subject' => $this->subject,
            'achieved_at' => $this->achievedAt,
        ];
    }
}
