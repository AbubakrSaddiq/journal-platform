<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueSubmission extends Model
{
    protected $fillable = [
        'issue_id',
        'submission_id',
        'page_number',
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}