<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Registros de asistencia** (Attendances).
 * 
 * Tabla: `attendances` registra la asistencia de cada aprendiz 
 * a cada clase ejecutada (`real_class`), con estado, hora de entrada 
 * y horas de ausencia calculadas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `attendances` vinculando aprendiz → clase ejecutada → estado.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();                                         // ID Attendance (clave primaria)

            // Relaciones principales
            $table->unsignedBigInteger('real_class_id');           // Clase ejecutada donde se registra
            $table->unsignedBigInteger('apprentice_id');           // Aprendiz (FK → users)
            $table->unsignedBigInteger('attendance_status_id');    // Estado de asistencia (FK → attendance_statuses)

            // Datos de control horario
            $table->time('entry_hour')->nullable();                // Hora de entrada registrada
            $table->integer('absent_hours')->nullable();           // Horas de ausencia calculadas
            $table->text('observations')->nullable();              // Observaciones del instructor

            // Relaciones foráneas
            $table->foreign('real_class_id')->references('id')->on('real_classes');    // FK → real_classes.id
            $table->foreign('apprentice_id')->references('id')->on('users');           // FK → users.id (aprendiz)
            $table->foreign('attendance_status_id')->references('id')->on('attendance_statuses'); // FK → attendance_statuses.id

            // Restricción de unicidad
            $table->unique(['real_class_id', 'apprentice_id'], 'unique_apprentice_per_class'); // Un registro por aprendiz por clase

            $table->timestamps();                                  // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `attendances` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
