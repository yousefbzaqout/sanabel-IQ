<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class BadgeUnlockedBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int $parentId,
        public readonly int $studentId,
        public readonly string $childName,
        public readonly string $badgeCode,
        public readonly string $badgeNameAr,
        public readonly string $badgeIcon,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('parent.'.$this->parentId),
            new PrivateChannel('student.'.$this->studentId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'badge.unlocked';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'student_id' => $this->studentId,
            'child_name' => $this->childName,
            'badge_code' => $this->badgeCode,
            'badge_name_ar' => $this->badgeNameAr,
            'badge_icon' => $this->badgeIcon,
        ];
    }
}
