<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Issue;
use App\Models\IssueSubmission;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\SubmissionReceived;
use App\Notifications\RevisionRequested;
use App\Notifications\EditorialDecisionMade;
use App\Notifications\SubmissionStatusChanged;

class SubmissionService
{
    /**
     * Valid state transitions for the submission workflow.
     * Key: current status, Value: array of allowed next statuses
     */
    protected array $allowedTransitions = [
        'submitted' => ['editorial_review', 'rejected'],
        'editorial_review' => ['under_review', 'rejected'],
        'under_review' => ['revision_required', 'rejected', 'accepted'],
        'revision_required' => ['editor_revision_check'],
        'editor_revision_check' => ['under_review', 'accepted', 'rejected'],
        'accepted' => ['editing', 'rejected'],
        'editing' => ['production'],
        'production' => ['scheduled'],
        'scheduled' => ['published'],
        'published' => [],
        'rejected' => [],
    ];

    /**
     * Transition submission to a new state.
     * Validates transition is allowed, updates status, logs action.
     *
     * @param Submission $submission
     * @param string $newStatus
     * @param ?int $userId User performing transition (for audit)
     * @param ?string $reason Optional reason for transition
     * @return bool Success
     */

    public function transitionTo(
        Submission $submission,
        string $newStatus,
        ?int $userId = null,
        ?string $reason = null
    ): bool {
        // Validate transition is allowed
        if (!$this->isTransitionAllowed($submission->status, $newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$submission->status}' to '{$newStatus}'"
            );
        }

        $oldStatus = $submission->status;
        $submission->status = $newStatus;
        $submission->save();

        // Log the transition
        $this->auditLog($submission, $userId, "status_change", [
            'from' => $oldStatus,
            'to' => $newStatus,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if a state transition is allowed.
     */
    public function isTransitionAllowed(string $currentStatus, string $newStatus): bool
    {
        if (!isset($this->allowedTransitions[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, $this->allowedTransitions[$currentStatus]);
    }

    /**
     * Get allowed next states for a submission.
     */
    public function getAllowedTransitions(Submission $submission): array
    {
        return $this->allowedTransitions[$submission->status] ?? [];
    }

    /**
     * Submit manuscript to journal (initial state).
     */
    public function submit(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'submitted', $userId, 'Initial submission');

        // Notify author

        $submission->author->notify(new SubmissionReceived($submission->load('journal')));
    }

    /**
     * Send to editorial review (desk rejection check).
     */
    public function sendToEditorialReview(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'editorial_review', $userId, 'Sent for editorial assessment');
    }

    /**
     * Send to peer review.
     */
   public function sendToReview(Submission $submission, ?int $userId = null): void
{
    // If still in submitted state, move to editorial_review first
    if ($submission->status === 'submitted') {
        $this->transitionTo($submission, 'editorial_review', $userId, 'Passed editorial assessment');
    }

    // Then move to under_review
    $this->transitionTo($submission, 'under_review', $userId, 'Assigned to reviewers');

    $submission->author->notify(new \App\Notifications\RevisionRequested(
        $submission,
        'under_review',
    ));
}

    /**
     * Request revision from author.
     */
    public function requestRevision(Submission $submission, string $revisionType, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'revision_required', $userId, "{$revisionType} revision requested");
        
        // Notify author
        $submission->author->notify(new RevisionRequested(
        $submission,
        $revisionType,
    ));
        }

    /**
     * Accept submission.
     */
    public function accept(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'accepted', $userId, 'Accepted for publication');

        // notify author
            $submission->author->notify(new EditorialDecisionMade($submission, 'accepted'));
        }

    /**
     * Reject submission (terminal state).
     */
    public function reject(Submission $submission, ?int $userId = null, ?string $reason = null): void
    {
        $this->transitionTo($submission, 'rejected', $userId, $reason ?? 'Submission rejected');

        // Notify author
        $submission->author->notify(new EditorialDecisionMade($submission, 'rejected'));
    }

    /**
     * Send to editing (copyediting + proofreading combined).
     */
    public function sendToEditing(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'editing', $userId, 'Sent for editing');

        // Notify author
        $submission->author->notify( new SubmissionStatusChanged($submission, 'editing'));

    }

    /**
     * Send to production.
     */
    public function sendToProduction(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'production', $userId, 'Sent to production');

        // Notify author
        $submission->author->notify( new SubmissionStatusChanged($submission, 'production'));
        }

    /**
     * Schedule a submission into an issue for publication.
     * Creates the issue_submissions pivot row and transitions status.
     */
    public function schedule(
        Submission $submission,
        int $issueId,
        ?int $userId = null
    ): void {
    $issue = Issue::findOrFail($issueId);

    if ($issue->journal_id !== $submission->journal_id) {
        throw new \InvalidArgumentException(
            'The selected issue belongs to a different journal.'
        );
    }

    if ($issue->published_at) {
        throw new \InvalidArgumentException(
            'Cannot schedule into a published issue.'
        );
    }

    // Enforce one-issue-per-submission
    $alreadyElsewhere = IssueSubmission::where('submission_id', $submission->id)
        ->where('issue_id', '!=', $issue->id)
        ->exists();

    if ($alreadyElsewhere) {
        throw new \InvalidArgumentException(
            'Submission is already scheduled in another issue.'
        );
    }

    // Idempotent — safe if re-run on the same issue
    IssueSubmission::firstOrCreate(
        [
            'issue_id' => $issue->id,
            'submission_id' => $submission->id,
        ],
        ['page_number' => null]
    );

    // Only transition if not already scheduled (e.g. re-adding to same issue)
    if ($submission->status !== 'scheduled') {
        $this->transitionTo(
            $submission,
            'scheduled',
            $userId,
            "Scheduled in Vol. {$issue->volume}, No. {$issue->issue_number}"
        );

        $submission->author->notify(
            new SubmissionStatusChanged($submission, 'scheduled')
        );
    }
    }

    /**
     * Publish submission.
     */
    public function publish(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'published', $userId, 'Published');
        // Notify author
        $submission->author->notify( new SubmissionStatusChanged($submission, 'published'));

    }

    /**
     * Log action to audit trail.
     */
    protected function auditLog(Submission $submission, ?int $userId, string $action, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'submission_id' => $submission->id,
            'action' => $action,
            'changes' => $changes,
            'timestamp' => now(),
        ]);
    }
}