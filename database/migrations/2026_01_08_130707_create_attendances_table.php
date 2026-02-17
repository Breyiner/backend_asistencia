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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('real_class_id');
            $table->unsignedBigInteger('apprentice_id');
            $table->unsignedBigInteger('attendance_status_id');

            $table->time('entry_hour')->nullable();
            $table->integer('absent_hours')->nullable();
            $table->text('observations')->nullable();

            $table->foreign('real_class_id')->references('id')->on('real_classes');
            $table->foreign('apprentice_id')->references('id')->on('users');
            $table->foreign('attendance_status_id')->references('id')->on('attendance_statuses');

            $table->unique(['real_class_id', 'apprentice_id'], 'unique_apprentice_per_class');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
