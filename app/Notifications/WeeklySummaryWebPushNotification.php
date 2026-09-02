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

    public const int MAX_BODY_LENGTH = 250;

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
        $totalActivities = collect($this->summary['children'])->sum(
            fn (array $child): int => (int) ($child['activities_completed'] ?? 0),
        );
        $totalXp = collect($this->summary['children'])->sum(
            fn (array $child): int => (int) ($child['xp_earned'] ?? 0),
        );
        $analyticsUrl = $this->summary['children'][0]['analytics_url'] ?? route('parent.analytics');

        $body = $totalActivities === 0 && $totalXp === 0
            ? __('No activities completed this week — your encouragement makes a difference!')
            : __(':activities activities completed and :xp XP earned this week.', [
                'activities' => $totalActivities,
                'xp' => $totalXp,
            ]);

        return (new WebPushMessage)
            ->title(__('Weekly Progress Summary'))
            ->body($this->truncateForMobileBanner($body))
            ->icon(url('/icons/sanabel-icon.svg'))
            ->badge(url('/icons/sanabel-icon.svg'))
            ->action(__('View report'), 'open_report')
            ->data([
                'url' => $analyticsUrl,
            ]);
    }

    private function truncateForMobileBanner(string $body): string
    {
        if (mb_strlen($body) <= self::MAX_BODY_LENGTH) {
            return $body;
        }

        return mb_substr($body, 0, self::MAX_BODY_LENGTH - 1).'…';
    }
}
