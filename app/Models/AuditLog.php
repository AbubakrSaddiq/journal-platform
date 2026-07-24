<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'submission_id',
        'action',
        'changes',
        'timestamp',
    ];

    protected $casts = [
        'changes' => 'json',
        'timestamp' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}