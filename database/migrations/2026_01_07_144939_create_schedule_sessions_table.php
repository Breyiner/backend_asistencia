<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('time_slot_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('day_id');

            $table->time('start_time');
            $table->time('end_time');

            $table->foreign('instructor_id')->references('id')->on('users');
            $table->foreign('schedule_id')->references('id')->on('schedules');
            $table->foreign('time_slot_id')->references('id')->on('time_slots');
            $table->foreign('classroom_id')->references('id')->on('classrooms');
            $table->foreign('day_id')->references('id')->on('days');

            $table->unique(
                ['schedule_id', 'day_id', 'time_slot_id', 'classroom_id'],
                'unique_classroom_per_schedule_day_time_slot'
            );

            $table->unique(
                ['schedule_id', 'day_id', 'time_slot_id', 'classroom_id', 'instructor_id'],
                'unique_instructor_per_schedule_day_time_slot_classroom'
            );

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_sessions');
    }
};