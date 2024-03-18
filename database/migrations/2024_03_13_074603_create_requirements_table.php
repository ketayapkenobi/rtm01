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
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->string('requirementID');
            $table->string('name');
            $table->text('description');
            $table->unsignedBigInteger('priority_id');
            $table->unsignedBigInteger('status_id');
            $table->string('project_id'); 
            $table->timestamps();

            $table->foreign('priority_id')->references('id')->on('priority');
            $table->foreign('status_id')->references('id')->on('status');
            $table->foreign('project_id')->references('projectID')->on('projects'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
