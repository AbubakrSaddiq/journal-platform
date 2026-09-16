<?php

namespace App\Notifications;

use App\Models\ReviewInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewInvitationResponded extends Notification
{
    public function __construct(
        public ReviewInvitation $invitation,
        public string $response // 'accepted' or 'declined'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verb = $this->response === 'accepted' ? 'accepted' : 'declined';

        return (new MailMessage)
            ->subject("Reviewer {$verb}: {$this->invitation->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line("A reviewer has {$verb} your invitation.")
            ->line("Reviewer: {$this->invitation->reviewer->name}")
            ->line("Title: {$this->invitation->submission->title}")
            ->action(
                'View Submission',
                url("/submissions/{$this->invitation->submission_id}")
            )
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        $verb = $this->response === 'accepted' ? 'accepted' : 'declined';

        return [
            'submission_id' => $this->invitation->submission_id,
            'invitation_id' => $this->invitation->id,
            'reviewer_name' => $this->invitation->reviewer->name,
            'response' => $this->response,
            'title' => $this->invitation->submission->title,
            'message' => "{$this->invitation->reviewer->name} has {$verb} the review invitation.",
        ];
    }
}