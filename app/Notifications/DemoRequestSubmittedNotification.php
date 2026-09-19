<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DemoRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DemoRequest $demoRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'demo_request_submitted',
            'title' => 'طلب عرض تجريبي جديد',
            'message' => sprintf(
                'طلب جديد من %s — %s (%s)',
                $this->demoRequest->school_name,
                $this->demoRequest->contact_name,
                $this->demoRequest->phone,
            ),
            'demo_request_id' => $this->demoRequest->id,
            'school_name' => $this->demoRequest->school_name,
            'contact_name' => $this->demoRequest->contact_name,
            'phone' => $this->demoRequest->phone,
            'email' => $this->demoRequest->email,
            'seat_range' => $this->demoRequest->seat_range,
        ];
    }
}
