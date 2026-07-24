<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\ReviewInvitation;
use App\Models\Review;
use App\Models\User;
use App\Models\AuditLog;

class ReviewService
{
    /**
     * Invite a reviewer to review a submission.
     */
    public function inviteReviewer(
        Submission $submission,
        User $reviewer,
        ?int $invitedBy = null
    ): ReviewInvitation {
        $invitation = ReviewInvitation::create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'pending',
            'invited_at' => now(),
        ]);

        $this->auditLog($submission, $invitedBy, 'reviewer_invited', [
            'reviewer_id' => $reviewer->id,
            'reviewer_name' => $reviewer->name,
        ]);

        // TODO: Send email notification to reviewer

        return $invitation;
    }

    /**
     * Reviewer accepts invitation.
     */
    public function acceptInvitation(ReviewInvitation $invitation, ?int $userId = null): void
    {
        $invitation->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        $this->auditLog($invitation->submission, $userId, 'reviewer_accepted', [
            'reviewer_id' => $invitation->reviewer_id,
        ]);
    }

    /**
     * Reviewer declines invitation.
     */
    public function declineInvitation(ReviewInvitation $invitation, ?int $userId = null): void
    {
        $invitation->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        $this->auditLog($invitation->submission, $userId, 'reviewer_declined', [
            'reviewer_id' => $invitation->reviewer_id,
        ]);

        // TODO: Notify editor that reviewer declined
    }

    /**
     * Reviewer submits review.
     */
    public function submitReview(
        ReviewInvitation $invitation,
        string $commentsForEditor,
        string $commentsForAuthor,
        string $recommendation,
        ?int $userId = null
    ): Review {
        // Validate recommendation
        $validRecommendations = ['accept', 'minor_revision', 'major_revision', 'reject', 'resubmit'];
        if (!in_array($recommendation, $validRecommendations)) {
            throw new \InvalidArgumentException("Invalid recommendation: {$recommendation}");
        }

        $review = Review::create([
            'submission_id' => $invitation->submission_id,
            'review_invitation_id' => $invitation->id,
            'comments_for_editor' => $commentsForEditor,
            'comments_for_author' => $commentsForAuthor,
            'recommendation' => $recommendation,
            'submitted_at' => now(),
        ]);

        $invitation->update(['status' => 'completed']);

        $this->auditLog($invitation->submission, $userId, 'review_submitted', [
            'reviewer_id' => $invitation->reviewer_id,
            'recommendation' => $recommendation,
        ]);

        // TODO: Notify editor that review is submitted

        return $review;
    }

    /**
     * Get all accepted reviewers for a submission.
     */
    public function getAcceptedReviewers(Submission $submission)
    {
        return $submission->reviewInvitations()
            ->where('status', 'accepted')
            ->with('reviewer')
            ->get()
            ->pluck('reviewer');
    }

    /**
     * Get all completed reviews for a submission.
     */
    public function getCompletedReviews(Submission $submission)
    {
        return $submission->reviews()
            ->whereHas('reviewInvitation', function ($query) {
                $query->where('status', 'completed');
            })
            ->get();
    }

    /**
     * Get review statistics for a submission.
     */
    public function getReviewStats(Submission $submission): array
    {
        $reviews = $this->getCompletedReviews($submission);

        $recommendations = [
            'accept' => 0,
            'minor_revision' => 0,
            'major_revision' => 0,
            'reject' => 0,
            'resubmit' => 0,
        ];

        foreach ($reviews as $review) {
            $recommendations[$review->recommendation]++;
        }

        return [
            'total_invited' => $submission->reviewInvitations()->count(),
            'total_accepted' => $submission->reviewInvitations()->where('status', 'accepted')->count(),
            'total_declined' => $submission->reviewInvitations()->where('status', 'declined')->count(),
            'total_completed' => $reviews->count(),
            'recommendations' => $recommendations,
        ];
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