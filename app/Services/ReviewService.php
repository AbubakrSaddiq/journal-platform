<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\ReviewInvitation;
use App\Models\Review;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\ReviewerInvited;
use App\Notifications\ReviewSubmitted;
use App\Notifications\ReviewInvitationResponded;
use Illuminate\Support\Facades\DB;

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

        $reviewer->notify(
            new ReviewerInvited($invitation->load('submission.journal'))
        );

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

        // ✅ Notify the editor who invited the reviewer
        $this->notifyEditors(
            $invitation,
            'accepted'
        );
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

        // ✅ Notify the editor who invited the reviewer
        $this->notifyEditors(
            $invitation,
            'declined'
        );
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
        $validRecommendations = ['accept', 'minor_revision', 'major_revision', 'reject', 'resubmit'];
        if (!in_array($recommendation, $validRecommendations)) {
            throw new \InvalidArgumentException("Invalid recommendation: {$recommendation}");
        }

        return DB::transaction(function () use (
            $invitation,
            $commentsForEditor,
            $commentsForAuthor,
            $recommendation,
            $userId
        ) {
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

            // ✅ Notify the editor(s)
            $this->notifyEditorsOfReview($invitation, $review);

            // ✅ Notify the author that a review is complete (no confidential details)
            $invitation->submission->author->notify(
                new \App\Notifications\RevisionRequested(
                    $invitation->submission,
                    'peer_review_completed',
                    'A reviewer has completed their assessment.'
                )
            );

            return $review;
        });
    }

    /**
     * Notify editors that a reviewer responded to an invitation.
     */
    protected function notifyEditors(ReviewInvitation $invitation, string $response): void
    {
        $submission = $invitation->submission()->with('journal')->first();
        if (!$submission) {
            return;
        }

        $editorIds = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.slug', ['editor', 'managing_editor', 'admin'])
            ->pluck('user_roles.user_id')
            ->unique();

        if ($editorIds->isEmpty()) {
            return;
        }

        User::whereIn('id', $editorIds)->each(
            fn (User $editor) => $editor->notify(
                new ReviewInvitationResponded($invitation, $response)
            )
        );
    }

    /**
     * Notify editors that a review has been submitted.
     */
    protected function notifyEditorsOfReview(ReviewInvitation $invitation, Review $review): void
    {
        $editorIds = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.slug', ['editor', 'managing_editor', 'admin'])
            ->pluck('user_roles.user_id')
            ->unique();

        if ($editorIds->isEmpty()) {
            return;
        }

        User::whereIn('id', $editorIds)->each(
            fn (User $editor) => $editor->notify(new ReviewSubmitted($review))
        );
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