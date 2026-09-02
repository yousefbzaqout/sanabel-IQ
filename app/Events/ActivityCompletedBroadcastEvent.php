<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ActivityCompletedBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int $parentId,
        public readonly string $childName,
        public readonly string $activityTitle,
        public readonly int $scorePercent,
        public readonly int $xpEarned,
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
        return 'activity.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'child_name' => $this->childName,
            'activity_title' => $this->activityTitle,
            'score_percent' => $this->scorePercent,
            'xp_earned' => $this->xpEarned,
        ];
    }
}
