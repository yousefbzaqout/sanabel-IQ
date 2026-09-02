<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyParentEncouragementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{
     *     period_start: string,
     *     period_end: string,
     *     children: list<array<string, mixed>>
     * }  $summary
     */
    public function __construct(public readonly array $summary) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Encourage Your Child to Start Learning'))
            ->view('emails.weekly-encouragement', [
                'parent' => $notifiable,
                'summary' => $this->summary,
                'analyticsUrl' => route('parent.analytics'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weekly_encouragement',
            'title' => __('Encourage Your Child to Start Learning'),
            'message' => __('حفّز طفلك لبدء التعلم'),
            'period_start' => $this->summary['period_start'],
            'period_end' => $this->summary['period_end'],
            'children' => $this->summary['children'],
            'analytics_url' => route('parent.analytics'),
        ];
    }
}
