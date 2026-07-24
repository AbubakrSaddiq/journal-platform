<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionAuthor extends Model
{
    protected $fillable = [
        'submission_id',
        'user_id',
        'author_order',
        'affiliation',
        'email',
        'name',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}