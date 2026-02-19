<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Estados de asistencia** (AttendanceStatuses).
 * 
 * Tabla: `attendance_statuses` define los códigos y nombres de los 
 * posibles estados de asistencia de un aprendiz (Presente, Ausente, Tardanza, etc.).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `attendance_statuses` con códigos únicos y descripciones.
     */
    public function up(): void
    {
        Schema::create('attendance_statuses', function (Blueprint $table) {
            $table->id();                                         // ID Status (clave primaria)
            $table->string('code', 50)->unique();                 // Código único (ej. "PRESENTE", "AUSENTE", "TARDANZA")
            $table->string('name', 50);                           // Nombre legible del estado (ej. "Presente", "Ausente")
            $table->string('description', 255)->nullable();       // Descripción detallada opcional
            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `attendance_statuses` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_statuses');
    }
};
