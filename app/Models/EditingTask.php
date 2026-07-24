<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditingTask extends Model
{
    protected $fillable = [
        'submission_id',
        'assigned_to_id',
        'status',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }
}