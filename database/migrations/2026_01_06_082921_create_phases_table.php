<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Fases/Etapas** formación.
 * 
 * Tabla: `phases` (Fase 1, Fase 2 del trimestre)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();                    // ID fase
            $table->string('name');          // "Fase 1", "Etapa Práctica"
            $table->text('description')->nullable(); // Descripción detallada
            $table->timestamps();            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
