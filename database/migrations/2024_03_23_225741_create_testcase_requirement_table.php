<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('testcase_requirement', function (Blueprint $table) {
            $table->id();
            $table->string('testcase_id');
            $table->string('requirement_id');
            $table->timestamps();

            $table->unique(['testcase_id', 'requirement_id']); // Ensures a pair of IDs is unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testcase_requirement');
    }
};
