<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;

    protected $table = 'requirements';

    protected $fillable = [
        'requirementID',
        'name',
        'description',
        'priority_id',
        'status_id',
        'project_id',
    ];

    public function getPriorityNameAttribute()
    {
        return $this->priority->name;
    }

    public function getStatusNameAttribute()
    {
        return $this->status->name;
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'projectID');
    }

    public function testCases()
    {
        return $this->belongsToMany(TestCase::class, 'testcase_requirement', 'requirement_id', 'testcase_id');
    }
}
