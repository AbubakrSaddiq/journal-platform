<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Submission $submission,
        public string $revisionType,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadline = $this->revisionType === 'major' ? '30-60 days' : '7-14 days';

        return (new MailMessage)
            ->subject("Revision Required: {$this->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line("A {$this->revisionType} revision has been requested for your manuscript.")
            ->line("Title: {$this->submission->title}")
            ->when($this->reason, fn($mail) => $mail->line("Editor's comments: {$this->reason}"))
            ->line("Please submit your revised manuscript within {$deadline}.")
            ->action('Submit Revision', url("/submissions/{$this->submission->id}"))
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'revision_type' => $this->revisionType,
            'message' => "A {$this->revisionType} revision has been requested.",
        ];
    }
}