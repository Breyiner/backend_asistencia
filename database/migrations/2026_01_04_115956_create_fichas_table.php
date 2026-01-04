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
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gestor_id');
            $table->string('ficha_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('training_program_id');
            $table->unsignedBigInteger('status_id');
            $table->foreign('gestor_id')->references('id')->on('users');
            $table->foreign('training_program_id')->references('id')->on('training_programs');
            $table->foreign('status_id')->references('id')->on('ficha_statuses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichas');
    }
};
