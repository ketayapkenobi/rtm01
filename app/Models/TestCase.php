<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    use HasFactory;

    protected $table = 'test_cases';

    protected $fillable = [
        'testcaseID',
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
}
