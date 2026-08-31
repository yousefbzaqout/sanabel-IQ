<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WeeklySummaryWebPushNotification extends Notification implements ShouldQueue
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
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        if (! method_exists($notifiable, 'routeNotificationForWebPush')) {
            return [];
        }

        if ($notifiable->routeNotificationForWebPush()->isEmpty()) {
            return [];
        }

        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $totalActivities = collect($this->summary['children'])->sum('activities_completed');
        $totalXp = collect($this->summary['children'])->sum('xp_earned');

        return (new WebPushMessage)
            ->title(__('Weekly Progress Summary'))
            ->body(__(':activities activities completed and :xp XP earned this week.', [
                'activities' => $totalActivities,
                'xp' => $totalXp,
            ]))
            ->icon(url('/icons/sanabel-icon.svg'))
            ->badge(url('/icons/sanabel-icon.svg'))
            ->action(__('View report'), 'open_report')
            ->data([
                'url' => route('parent.analytics'),
            ]);
    }
}
