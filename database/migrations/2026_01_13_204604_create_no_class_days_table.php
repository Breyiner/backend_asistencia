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
        Schema::create('no_class_days', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ficha_id');
            $table->date('date');
            $table->string('reason')->nullable();

            $table->foreign('ficha_id')->references('id')->on('fichas');

            $table->unique(['ficha_id', 'date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('no_class_days');
    }
};
