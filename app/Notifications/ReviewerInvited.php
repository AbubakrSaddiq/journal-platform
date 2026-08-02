<?php

namespace App\Notifications;

use App\Models\ReviewInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReviewInvitation $invitation)
    {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Review Invitation: {$this->invitation->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line('You have been invited to review the following manuscript:')
            ->line("Title: {$this->invitation->submission->title}")
            ->line("Journal: {$this->invitation->submission->journal->title}")
            ->line('Please accept or decline the invitation within 7 days.')
            ->action('Respond to Invitation', url("/reviews/{$this->invitation->id}"))
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'submission_id' => $this->invitation->submission_id,
            'message' => 'You have been invited to review a manuscript.',
        ];
    }
}