<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Requirement;
use App\Models\TestCase;

class Priority extends Model
{
    use HasFactory;

    protected $table = 'priority';

    protected $fillable = [
        'name',
    ];

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }

    public function testcases()
    {
        return $this->hasMany(TestCase::class);
    }
}
