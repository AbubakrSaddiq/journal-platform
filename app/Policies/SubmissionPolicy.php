<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        // Author can always view 
        if ($user->id === $submission->author_id) {
            return true;
        }

        // Editors can view
        if ($this->isEditor($user)) {
            return true;
        }

        // ✅ Invited reviewers (accepted or completed) can view
        if ($this->isInvitedReviewer($user, $submission)) {
            return true;
        }

        return false;
    }

    public function update(User $user, Submission $submission): bool
    {
        if ($user->id !== $submission->author_id) {
            return false;
        }
        return in_array($submission->status, ['submitted', 'editorial_review']);
    }

    public function sendToReview(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function requestRevision(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function accept(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function sendToEditing(User $user, Submission $submission): bool {
        return $this->isEditor($user);
    }

    public function sendToProduction(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function schedule(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function publish(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    public function reject(User $user, Submission $submission): bool
    {
        return $this->isEditor($user);
    }

    private function isEditor(User $user): bool
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->whereIn('roles.slug', ['editor', 'managing_editor', 'admin'])
            ->exists();
    }

    /**
     * ✅ Reviewer has an accepted invitation for this submission.
     */
    private function isInvitedReviewer(User $user, Submission $submission): bool
    {
        return $submission->reviewInvitations()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->exists();
    }
}