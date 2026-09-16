<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionStatusChanged extends Notification
{
    /**
     * Human-readable labels for each status.
     */
    protected array $labels = [
        'editing' => 'Editing',
        'production' => 'Production',
        'scheduled' => 'Scheduled for Publication',
        'published' => 'Published',
    ];

    protected array $messages = [
        'editing' => 'Your manuscript has been accepted and is now in the editing stage.',
        'production' => 'Your manuscript is now in production (typesetting, DOI assignment, and layout).',
        'scheduled' => 'Your manuscript has been scheduled for publication in an upcoming issue.',
        'published' => 'Your manuscript has been published and is now publicly available.',
    ];

    public function __construct(
        public Submission $submission,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->labels[$this->newStatus] ?? ucfirst($this->newStatus);
        $message = $this->messages[$this->newStatus]
            ?? "Your submission status has changed to {$label}.";

        return (new MailMessage)
            ->subject("{$label}: {$this->submission->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line($message)
            ->line("Title: {$this->submission->title}")
            ->action(
                'View Submission',
                url("/submissions/{$this->submission->id}")
            )
            ->salutation('Best regards, The Editorial Team');
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->labels[$this->newStatus] ?? ucfirst($this->newStatus);

        return [
            'submission_id' => $this->submission->id,
            'status' => $this->newStatus,
            'label' => $label,
            'title' => $this->submission->title,
            'message' => "Your manuscript \"{$this->submission->title}\" is now in {$label}.",
        ];
    }
}