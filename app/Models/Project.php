<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
Use App\Models\User;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'projectID',
        'projectName',
        'projectDesc',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members', 'projectID', 'userID');
    }
    
    public function requirements()
    {
        return $this->hasMany(Requirement::class, 'projectID');
    }
}
