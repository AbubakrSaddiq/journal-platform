<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewInvitation extends Model
{
    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'status',
        'invited_at',
        'responded_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}