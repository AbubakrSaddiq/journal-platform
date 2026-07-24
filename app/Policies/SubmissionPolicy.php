<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Submission;

class SubmissionPolicy
{
    /**
     * View submission.
     */
    public function view(User $user, Submission $submission): bool
    {
        // Author views own
        if ($user->id === $submission->author_id) {
            return true;
        }

        // Editor views journal submissions
        if ($user->roles()->where('journal_id', $submission->journal_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Update submission (author only, before review).
     */
    public function update(User $user, Submission $submission): bool
    {
        if ($user->id !== $submission->author_id) {
            return false;
        }

        return in_array($submission->status, ['submitted', 'editorial_review']);
    }

    /**
     * Send to review (editor only).
     */
    public function sendToReview(User $user, Submission $submission): bool
    {
        return $user->roles()
            ->whereIn('slug', ['editor', 'managing_editor'])
            ->where('journal_id', $submission->journal_id)
            ->exists();
    }

    /**
     * Request revision (editor only).
     */
    public function requestRevision(User $user, Submission $submission): bool
    {
        return $this->sendToReview($user, $submission);
    }

    /**
     * Accept (editor only).
     */
    public function accept(User $user, Submission $submission): bool
    {
        return $this->sendToReview($user, $submission);
    }

    /**
     * Reject (editor only).
     */
    public function reject(User $user, Submission $submission): bool
    {
        return $this->sendToReview($user, $submission);
    }
}