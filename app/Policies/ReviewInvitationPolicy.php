<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReviewInvitation;

class ReviewInvitationPolicy
{
    /**
     * Only invited reviewer can view invitation.
     */
    public function view(User $user, ReviewInvitation $invitation): bool
    {
        return $user->id === $invitation->reviewer_id;
    }
}