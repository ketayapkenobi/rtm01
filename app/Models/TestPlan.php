<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestPlan extends Model
{
    use HasFactory;

    protected $table = 'test_plans';

    protected $fillable = [
        'testplanID',
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

    public function testCases()
    {
        return $this->belongsToMany(TestCase::class, 'testplan_testcase', 'testplan_id', 'testcase_id');
    }

}
