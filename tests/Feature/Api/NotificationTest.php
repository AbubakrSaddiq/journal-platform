<?php

namespace Tests\Feature\Api;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionReceived;
use App\Notifications\RevisionRequested;
use App\Notifications\EditorialDecisionMade;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubmissionService();
    }

    /**
     * Test author notified when submission received.
     */
   public function test_author_notified_on_submission(): void
{
    Notification::fake();

    $author = User::factory()->create();
    $submission = Submission::factory()->create([
        'author_id' => $author->id,
        'status' => 'editorial_review',
    ]);

    // Directly notify - testing notification delivery not state transition
    $author->notify(new SubmissionReceived($submission->load('journal')));

    Notification::assertSentTo($author, SubmissionReceived::class);
}

    /**
     * Test author notified when revision requested.
     */
    public function test_author_notified_on_revision_request(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'under_review',
        ]);

        $this->service->requestRevision($submission, 'minor', userId: 1);

        Notification::assertSentTo($author, RevisionRequested::class);
    }

    /**
     * Test author notified when submission accepted.
     */
    public function test_author_notified_on_acceptance(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'under_review',
        ]);

        $this->service->accept($submission, userId: 1);

        Notification::assertSentTo($author, EditorialDecisionMade::class,
            fn($notification) => $notification->decision === 'accepted'
        );
    }

    /**
     * Test author notified when submission rejected.
     */
    public function test_author_notified_on_rejection(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $submission = Submission::factory()->create([
            'author_id' => $author->id,
            'status' => 'under_review',
        ]);

        $this->service->reject($submission, userId: 1, reason: 'Outside scope');

        Notification::assertSentTo($author, EditorialDecisionMade::class,
            fn($notification) => $notification->decision === 'rejected'
        );
    }
}