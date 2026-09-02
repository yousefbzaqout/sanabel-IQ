<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklySummaryMailable extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{
     *     period_start: string,
     *     period_end: string,
     *     children: list<array{
     *         student_id: int,
     *         name: string,
     *         grade_level: int,
     *         xp_earned: int,
     *         quiz_accuracy_percent: int,
     *         activities_completed: int,
     *         analytics_url: string
     *     }>
     * }  $digest
     */
    public function __construct(
        public User $parent,
        public array $digest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Weekly Progress Summary for Your Children'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary',
            with: [
                'parent' => $this->parent,
                'summary' => $this->digest,
            ],
        );
    }
}
