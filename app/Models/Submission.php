<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Submission extends Model
{
    use HasFactory; 
    
    protected $fillable = [
        'journal_id',
        'section_id',
        'author_id',
        'title',
        'abstract',
        'keywords',
        'cover_letter',
        'status',
        'current_version_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions()
    {
        return $this->hasMany(SubmissionVersion::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(SubmissionVersion::class, 'current_version_id');
    }

    public function coAuthors()
    {
        return $this->hasMany(SubmissionAuthor::class);
    }

    public function files()
    {
        return $this->hasManyThrough(SubmissionFile::class, SubmissionVersion::class);
    }

    public function editorialAssessments()
    {
        return $this->hasMany(EditorialAssessment::class);
    }

    public function reviewInvitations()
    {
        return $this->hasMany(ReviewInvitation::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function editorialDecisions()
    {
        return $this->hasMany(EditorialDecision::class);
    }

    public function editingTasks()
    {
        return $this->hasMany(EditingTask::class);
    }

    public function productionTasks()
    {
        return $this->hasMany(ProductionTask::class);
    }

    public function issueSubmissions()
    {
        return $this->hasMany(IssueSubmission::class);
    }

    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issue_submissions');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}