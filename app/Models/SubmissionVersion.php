<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionVersion extends Model
{
    protected $fillable = [
        'submission_id',
        'version_number',
        'uploaded_by_id',
        'upload_notes',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function files()
    {
        return $this->hasMany(SubmissionFile::class);
    }
}