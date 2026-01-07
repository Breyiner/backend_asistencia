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
        Schema::create('ficha_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('ficha_id');
            $table->unsignedBigInteger('phase_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->unique(['ficha_id', 'term_id'], 'unique_ficha_term');
            $table->foreign('term_id')->references('id')->on('terms');
            $table->foreign('ficha_id')->references('id')->on('fichas');
            $table->foreign('phase_id')->references('id')->on('phases');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha_terms');
    }
};
