<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Submission $submission)
    {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Submission Received: {$this->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line('Your manuscript has been successfully submitted.')
            ->line("Title: {$this->submission->title}")
            ->line("Journal: {$this->submission->journal->title}")
            ->line('You will be notified when the editorial team reviews your submission.')
            ->action('View Submission', url("/submissions/{$this->submission->id}"))
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'title' => $this->submission->title,
            'message' => 'Your submission has been received.',
        ];
    }
}