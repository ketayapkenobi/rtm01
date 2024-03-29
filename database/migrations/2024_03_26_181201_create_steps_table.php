<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStepsTable extends Migration
{
    public function up()
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->string('testcase_id');
            $table->text('action');
            $table->text('input');
            $table->text('expected_result');
            $table->integer('step_order');
            $table->timestamps();
        });        
    }

    public function down()
    {
        Schema::dropIfExists('steps');
    }
}

