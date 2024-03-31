<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestplanTestcaseTable extends Migration
{
    public function up()
    {
        Schema::create('testplan_testcase', function (Blueprint $table) {
            $table->id();
            $table->string('testplan_id');
            $table->string('testcase_id');
            $table->timestamps();

            $table->unique(['testplan_id', 'testcase_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('testplan_testcase');
    }
}
