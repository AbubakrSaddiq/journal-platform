<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialAssessment extends Model
{
    protected $fillable = [
        'submission_id',
        'assessed_by_id',
        'decision',
        'reason',
        'assessed_at',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by_id');
    }
}