<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Días de la semana** (Days).
 * 
 * Tabla: `days` define los días base del calendario (ej. Lunes a Domingo),
 * usada para referenciar periodos u horarios dentro del sistema académico.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `days` con el nombre del día y su número de orden único.
     */
    public function up(): void
    {
        Schema::create('days', function (Blueprint $table) {
            $table->id();                                        // ID Day (clave primaria)
            $table->string('name', 20);                          // Nombre del día (ej. "Lunes")
            $table->tinyInteger('day_number')->unsigned()->unique(); // Número del día (1–7), único
            $table->timestamps();                                // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `days` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('days');
    }
};
