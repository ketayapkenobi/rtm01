<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestExecution extends Model
{
    use HasFactory;

    protected $table = 'test_executions';

    protected $fillable = [
        'result',
    ];

    public function steps()
    {
        return $this->hasMany(Step::class, 'testexecution_id', 'testexecutionID');
    }
}
