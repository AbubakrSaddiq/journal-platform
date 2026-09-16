<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmitted extends Notification
{
    public function __construct(
        public Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Review Submitted: {$this->review->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line('A reviewer has submitted their assessment.')
            ->line("Title: {$this->review->submission->title}")
            ->line("Recommendation: {$this->review->recommendation}")
            ->action(
                'View Submission',
                url("/submissions/{$this->review->submission_id}")
            )
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->review->submission_id,
            'review_id' => $this->review->id,
            'recommendation' => $this->review->recommendation,
            'title' => $this->review->submission->title,
            'message' => "A review has been submitted for \"{$this->review->submission->title}\".",
        ];
    }
}