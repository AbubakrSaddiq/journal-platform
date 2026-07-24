<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Journal extends Model
{
    use HasFactory; 

    protected $fillable = [
        'title',
        'slug',
        'issn',
        'description',
        'settings',
        'published_at',
    ];

    protected $casts = [
        'settings' => 'json',
        'published_at' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }
}