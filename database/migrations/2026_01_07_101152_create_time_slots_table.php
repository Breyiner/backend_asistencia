<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Franjas horarias** (TimeSlots).
 * 
 * Tabla: `time_slots` define los rangos horarios disponibles 
 * (ej. mañana, tarde, noche) para estructurar los horarios académicos.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `time_slots` con códigos y rangos de tiempo.
     */
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();                                         // ID TimeSlot (clave primaria)
            $table->string('code')->unique();                     // Código único del bloque horario (ej. "M1", "T2")
            $table->string('name');                               // Nombre descriptivo del bloque (ej. "Mañana 1")
            $table->time('start_time');                           // Hora de inicio del bloque
            $table->time('end_time');                             // Hora de finalización del bloque
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `time_slots` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
