<?php

namespace Tests\Unit\Services;

use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubmissionService();
    }

    /**
     * Test valid state transitions.
     */
    public function test_can_transition_from_submitted_to_editorial_review(): void
    {
        $submission = Submission::factory()->create(['status' => 'submitted']);

        $this->service->sendToEditorialReview($submission, userId: 1);

        $this->assertEquals('editorial_review', $submission->fresh()->status);
    }

    /**
     * Test invalid state transitions throw error.
     */
    public function test_cannot_transition_from_published_to_submitted(): void
    {
        $submission = Submission::factory()->create(['status' => 'published']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->transitionTo($submission, 'submitted', userId: 1);
    }

    /**
     * Test all allowed transitions are correct.
     */
    public function test_allowed_transitions(): void
    {
        $submission = Submission::factory()->create(['status' => 'submitted']);

        $allowed = $this->service->getAllowedTransitions($submission);

        $this->assertContains('editorial_review', $allowed);
        $this->assertContains('rejected', $allowed);
        $this->assertNotContains('published', $allowed);
    }

    /**
     * Test accept creates audit log.
     */
    public function test_accept_logs_audit_entry(): void
    {
$submission = Submission::factory()->create(['status' => 'under_review']);
        $user = User::factory()->create();

        $this->service->accept($submission, userId: $user->id);

        $this->assertDatabaseHas('audit_log', [
            'submission_id' => $submission->id,
            'user_id' => $user->id,
            'action' => 'status_change',
        ]);
    }

    /**
     * Test workflow: submitted → editorial_review → under_review → accepted
     */
    public function test_complete_workflow_sequence(): void
    {
        $submission = Submission::factory()->create(['status' => 'submitted']);
        $user = User::factory()->create();

        $this->service->sendToEditorialReview($submission, userId: $user->id);
        $this->assertEquals('editorial_review', $submission->fresh()->status);

        $this->service->sendToReview($submission, userId: $user->id);
        $this->assertEquals('under_review', $submission->fresh()->status);

        $this->service->accept($submission, userId: $user->id);
        $this->assertEquals('accepted', $submission->fresh()->status);

        // Verify audit log captured all transitions
        $this->assertCount(3, $submission->auditLogs);
    }
}