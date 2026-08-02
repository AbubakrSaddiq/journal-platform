<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EditorialDecisionMade extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Submission $submission,
        public string $decision
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $messages = [
            'accepted' => 'We are pleased to inform you that your manuscript has been accepted for publication.',
            'rejected' => 'We regret to inform you that your manuscript has not been accepted for publication.',
        ];

        $message = $messages[$this->decision]
            ?? "A decision has been made regarding your manuscript.";

        return (new MailMessage)
            ->subject("Editorial Decision: {$this->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line($message)
            ->line("Title: {$this->submission->title}")
            ->action('View Submission', url("/submissions/{$this->submission->id}"))
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'decision' => $this->decision,
            'message' => "Editorial decision: {$this->decision}",
        ];
    }
}