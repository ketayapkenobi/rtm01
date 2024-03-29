<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use HasFactory;

    protected $fillable = [
        'testcase_id',
        'action',
        'input',
        'expected_result',
        'step_order',
    ];

    public function testCase()
    {
        return $this->belongsTo(TestCase::class, 'testcase_id');
    }
}
