<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Ficha + Trimestre** (FichaTerm).
 * 
 * Tabla: `ficha_terms` combina Ficha+Trimestre+Fase
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ficha_terms', function (Blueprint $table) {
            $table->id();                                    // ID FichaTerm
            $table->unsignedBigInteger('term_id');           // Trimestre (1,2,3,4)
            $table->unsignedBigInteger('ficha_id');          // Ficha SENA
            $table->unsignedBigInteger('phase_id');          // Fase del trimestre
            $table->date('start_date');                      // Inicio periodo
            $table->date('end_date');                        // Fin periodo
            $table->boolean('is_current')->default(false);   // Trimestre activo
            $table->unique(['ficha_id', 'term_id'], 'unique_ficha_term'); // Compuesta
            $table->foreign('term_id')->references('id')->on('terms');
            $table->foreign('ficha_id')->references('id')->on('fichas');
            $table->foreign('phase_id')->references('id')->on('phases');
            $table->timestamps();                            // created_at, updated_at
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
