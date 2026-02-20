<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Programas Formación** SENA.
 * 
 * Tabla: `training_programs` (Técnico Desarrollo Software, etc)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();                                    // ID programa
            $table->string('name')->unique();                // "Técnico Desarrollo Software"
            $table->string('description')->nullable();       // Descripción programa
            $table->string("duration");                      // Meses totales (ej: "480")
            $table->unsignedBigInteger('qualification_level_id'); // FK nivel
            $table->foreign('qualification_level_id')->references('id')->on('qualification_levels');
            $table->unsignedBigInteger('area_id')->nullable();           // FK área
            $table->foreign('area_id')->references('id')->on('areas');
            $table->timestamps();                            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
