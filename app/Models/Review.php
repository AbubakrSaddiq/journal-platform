<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'submission_id',
        'review_invitation_id',
        'comments_for_editor',
        'comments_for_author',
        'recommendation',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewInvitation()
    {
        return $this->belongsTo(ReviewInvitation::class);
    }

    public function reviewer()
    {
        return $this->hasManyThrough(User::class, ReviewInvitation::class, 'id', 'id', 'review_invitation_id', 'reviewer_id');
    }
}