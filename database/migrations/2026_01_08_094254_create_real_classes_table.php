<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Clases ejecutadas** (RealClasses).
 * 
 * Tabla: `real_classes` registra las clases que **realmente se impartieron**, 
 * vinculando el horario planificado (`schedule_sessions`) con la fecha específica 
 * de ejecución, incluyendo posibles cambios o reprogramaciones.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `real_classes` para rastrear clases ejecutadas en fechas específicas.
     */
    public function up(): void
    {
        Schema::create('real_classes', function (Blueprint $table) {
            $table->id();                                         // ID RealClass (clave primaria)

            // Relaciones principales
            $table->unsignedBigInteger('instructor_id');           // Instructor que impartió la clase
            $table->unsignedBigInteger('class_type_id');           // Tipo de clase ejecutada (FK → class_types)
            $table->unsignedBigInteger('classroom_id');            // Ambiente utilizado
            $table->unsignedBigInteger('time_slot_id');            // Franja horaria aplicada
            $table->unsignedBigInteger('schedule_session_id');     // Sesión del horario planificado origen

            // Fechas y horarios de ejecución
            $table->date('execution_date');                        // Fecha real de la clase
            $table->time('start_hour');                            // Hora de inicio real
            $table->time('end_hour');                              // Hora de finalización real
            $table->date('original_date')->nullable();             // Fecha original planificada (para reprogramaciones)

            // Observaciones adicionales
            $table->text('observations')->nullable();              // Notas, cambios, incidencias, etc.

            // Relaciones foráneas
            $table->foreign('instructor_id')->references('id')->on('users');       // FK → users.id (instructor)
            $table->foreign('class_type_id')->references('id')->on('class_types'); // FK → class_types.id
            $table->foreign('classroom_id')->references('id')->on('classrooms');   // FK → classrooms.id
            $table->foreign('time_slot_id')->references('id')->on('time_slots');   // FK → time_slots.id
            $table->foreign('schedule_session_id')->references('id')->on('schedule_sessions'); // FK → schedule_sessions.id

            $table->timestamps();                                  // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `real_classes` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_classes');
    }
};
