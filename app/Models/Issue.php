<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'journal_id',
        'volume',
        'issue_number',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function issueSubmissions()
    {
        return $this->hasMany(IssueSubmission::class);
    }

    public function submissions()
    {
        return $this->belongsToMany(Submission::class, 'issue_submissions');
    }
}