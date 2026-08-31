<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ParentLearningGoal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class GoalAchievedWebPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const int MAX_BODY_LENGTH = 250;

    public function __construct(public ParentLearningGoal $goal) {}

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
        $this->goal->loadMissing(['student', 'subject']);
        $subjectLabel = $this->goal->subject?->name ?? __('General');
        $body = __(':student completed the learning goal for :subject.', [
            'student' => $this->goal->student->name,
            'subject' => $subjectLabel,
        ]);

        return (new WebPushMessage)
            ->title('🎉 إنجاز جديد!')
            ->body($this->truncateForMobileBanner($body))
            ->icon(url('/icons/sanabel-icon.svg'))
            ->badge(url('/icons/sanabel-icon.svg'))
            ->action(__('View dashboard'), 'open_dashboard')
            ->data([
                'url' => route('dashboard'),
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
