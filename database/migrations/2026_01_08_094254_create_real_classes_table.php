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
        Schema::create('real_classes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('class_type_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('schedule_session_id');

            $table->date('execution_date');
            $table->time('start_hour');
            $table->time('end_hour');
            $table->date('original_date')->nullable();

            $table->text('observations')->nullable();

            $table->foreign('instructor_id')->references('id')->on('users');
            $table->foreign('class_type_id')->references('id')->on('class_types');
            $table->foreign('classroom_id')->references('id')->on('classrooms');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('schedule_session_id')->references('id')->on('schedule_sessions');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_classes');
    }
};
