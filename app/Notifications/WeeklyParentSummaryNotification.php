<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyParentSummaryNotification extends Notification implements ShouldQueue
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
            ->subject(__('Weekly Progress Summary for Your Children'))
            ->view('emails.weekly-summary', [
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
        $totalActivities = collect($this->summary['children'])->sum('activities_completed');
        $totalXp = collect($this->summary['children'])->sum('xp_earned');

        return [
            'type' => 'weekly_summary',
            'title' => __('Weekly Progress Summary'),
            'message' => __(':activities activities completed and :xp XP earned this week.', [
                'activities' => $totalActivities,
                'xp' => $totalXp,
            ]),
            'period_start' => $this->summary['period_start'],
            'period_end' => $this->summary['period_end'],
            'children' => $this->summary['children'],
            'analytics_url' => route('parent.analytics'),
        ];
    }
}
