<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialDecision extends Model
{
    protected $fillable = [
        'submission_id',
        'decided_by_id',
        'decision',
        'reason',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }
}