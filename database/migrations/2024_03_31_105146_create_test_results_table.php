<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->string('testexecution_id');
            $table->unsignedBigInteger('step_id');
            $table->unsignedBigInteger('result_id');
            $table->string('actual_result')->nullable(); // New field: actual_result
            $table->string('checked_by')->nullable();   // New field: checked_by
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};

