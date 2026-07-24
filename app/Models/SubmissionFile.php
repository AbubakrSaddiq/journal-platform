<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $fillable = [
        'submission_version_id',
        'file_path',
        'original_filename',
        'file_type',
        'file_role',
        'file_size',
    ];

    public function submissionVersion()
    {
        return $this->belongsTo(SubmissionVersion::class);
    }
}