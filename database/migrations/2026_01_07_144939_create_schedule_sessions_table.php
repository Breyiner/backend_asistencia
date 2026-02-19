<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración **Sesiones de horario** (ScheduleSessions).
 * 
 * Tabla: `schedule_sessions` representa cada bloque de sesión agendado 
 * (clase, formación o módulo) dentro de un horario (`schedule`), 
 * combinando instructor, día, franja horaria y ambiente.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla `schedule_sessions` con relaciones hacia instructor, horario, 
     * franja horaria, ambiente y día.
     */
    public function up(): void
    {
        Schema::create('schedule_sessions', function (Blueprint $table) {
            $table->id();                                         // ID Session (clave primaria)

            $table->unsignedBigInteger('instructor_id');           // Instructor asignado (FK → users)
            $table->unsignedBigInteger('schedule_id');             // Horario base (FK → schedules)
            $table->unsignedBigInteger('time_slot_id');            // Franja horaria (FK → time_slots)
            $table->unsignedBigInteger('classroom_id');            // Ambiente de formación (FK → classrooms)
            $table->unsignedBigInteger('day_id');                  // Día de la semana (FK → days)

            $table->time('start_time');                            // Hora de inicio de la sesión
            $table->time('end_time');                              // Hora de finalización de la sesión

            // Relaciones foráneas
            $table->foreign('instructor_id')->references('id')->on('users');      // FK → users.id (instructor)
            $table->foreign('schedule_id')->references('id')->on('schedules');    // FK → schedules.id
            $table->foreign('time_slot_id')->references('id')->on('time_slots');  // FK → time_slots.id
            $table->foreign('classroom_id')->references('id')->on('classrooms');  // FK → classrooms.id
            $table->foreign('day_id')->references('id')->on('days');              // FK → days.id

            // Restricciones de unicidad compuesta
            $table->unique(
                ['schedule_id', 'day_id', 'time_slot_id', 'classroom_id'],
                'unique_classroom_per_schedule_day_time_slot'
            ); // Evita duplicidad de ambiente en el mismo día y bloque

            $table->unique(
                ['schedule_id', 'day_id', 'time_slot_id', 'classroom_id', 'instructor_id'],
                'unique_instructor_per_schedule_day_time_slot_classroom'
            ); // Evita que un instructor esté duplicado en el mismo bloque

            $table->timestamps();                                 // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla `schedule_sessions` si existe.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_sessions');
    }
};
