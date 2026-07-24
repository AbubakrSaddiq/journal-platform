<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

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
        $this->transitionTo($submission, 'under_review', $userId, 'Assigned to reviewers');
    }

    /**
     * Request revision from author.
     */
    public function requestRevision(Submission $submission, string $revisionType, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'revision_required', $userId, "{$revisionType} revision requested");
    }

    /**
     * Accept submission.
     */
    public function accept(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'accepted', $userId, 'Accepted for publication');
    }

    /**
     * Reject submission (terminal state).
     */
    public function reject(Submission $submission, ?int $userId = null, ?string $reason = null): void
    {
        $this->transitionTo($submission, 'rejected', $userId, $reason ?? 'Submission rejected');
    }

    /**
     * Send to editing (copyediting + proofreading combined).
     */
    public function sendToEditing(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'editing', $userId, 'Sent for editing');
    }

    /**
     * Send to production.
     */
    public function sendToProduction(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'production', $userId, 'Sent to production');
    }

    /**
     * Schedule for publication.
     */
    public function schedule(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'scheduled', $userId, 'Scheduled for publication');
    }

    /**
     * Publish submission.
     */
    public function publish(Submission $submission, ?int $userId = null): void
    {
        $this->transitionTo($submission, 'published', $userId, 'Published');
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