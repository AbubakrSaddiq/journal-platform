<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Section extends Model
{
    use HasFactory; 

    protected $fillable = [
        'journal_id',
        'title',
        'slug',
        'description',
        'sort_order',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}