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
        Schema::create('schedule_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('day_id');

            $table->time('start_time');
            $table->time('end_time');

            $table->foreign('instructor_id')->references('id')->on('users');
            $table->foreign('schedule_id')->references('id')->on('schedules');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('classroom_id')->references('id')->on('classrooms');
            $table->foreign('day_id')->references('id')->on('days');

            // Un ambiente solo se puede ocupar una vez por jornada
            $table->unique(['shift_id', 'classroom_id'], 'unique_classroom_per_shift');

            // Un instructor solo una vez por ambiente y jornada a la vez
            $table->unique(['shift_id', 'classroom_id', 'instructor_id'], 'unique_instructor_per_classroom_shift');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_sessions');
    }
};
