<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'affiliation',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Submissions authored
    public function submissions()
    {
        return $this->hasMany(Submission::class, 'author_id');
    }

    // Roles assigned to this user
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Reviews submitted by this user
    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    // Editorial assessments performed
    public function editorialAssessments()
    {
        return $this->hasMany(EditorialAssessment::class, 'assessed_by_id');
    }

    // Editorial decisions made
    public function editorialDecisions()
    {
        return $this->hasMany(EditorialDecision::class, 'decided_by_id');
    }

    // Editing tasks assigned
    public function editingTasks()
    {
        return $this->hasMany(EditingTask::class, 'assigned_to_id');
    }

    // Production tasks assigned
    public function productionTasks()
    {
        return $this->hasMany(ProductionTask::class, 'assigned_to_id');
    }

    // Review invitations sent to this user
    public function reviewInvitations()
    {
        return $this->hasMany(ReviewInvitation::class, 'reviewer_id');
    }

    // Submission versions uploaded by this user
    public function submissionVersions()
    {
        return $this->hasMany(SubmissionVersion::class, 'uploaded_by_id');
    }

    // Co-authorships
    public function coAuthorships()
    {
        return $this->hasMany(SubmissionAuthor::class);
    }

    // Audit log entries
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}