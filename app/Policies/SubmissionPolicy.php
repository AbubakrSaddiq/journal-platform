<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        if ($user->id === $submission->author_id) {
            return true;
        }
        return $this->isEditor($user);
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
}