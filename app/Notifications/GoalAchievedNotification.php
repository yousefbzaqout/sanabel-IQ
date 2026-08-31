<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ParentLearningGoal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GoalAchievedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ParentLearningGoal $goal) {}

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
        $subjectLabel = $this->goal->subject?->name ?? __('General');

        return [
            'type' => 'goal_achieved',
            'title' => __('Learning Goal Achieved'),
            'message' => __(':student completed the learning goal for :subject.', [
                'student' => $this->goal->student->name,
                'subject' => $subjectLabel,
            ]),
            'goal_id' => $this->goal->id,
            'student_id' => $this->goal->student_id,
            'student_name' => $this->goal->student->name,
            'subject' => $subjectLabel,
            'target_activity_count' => $this->goal->target_activity_count,
            'target_xp' => $this->goal->target_xp,
        ];
    }
}
