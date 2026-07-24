<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTask extends Model
{
    protected $fillable = [
        'submission_id',
        'assigned_to_id',
        'doi',
        'pagination',
        'metadata',
        'outputs',
        'status',
    ];

    protected $casts = [
        'metadata' => 'json',
        'outputs' => 'json',
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