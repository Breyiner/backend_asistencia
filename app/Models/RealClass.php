<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Clase Real** ejecutada.
 *
 * Representa una clase efectivamente dictada (no solo programada).
 */
class RealClass extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'instructor_id',     // Instructor que dictó la clase
        'class_type_id',     // Tipo: Teórica, Práctica, Laboratorio
        'classroom_id',      // Aula donde se dictó
        'time_slot_id',      // Franja horaria ejecutada
        'schedule_session_id', // Sesión de horario origen
        'execution_date',    // Fecha real de ejecución
        'start_hour',        // Hora inicio real
        'end_hour',          // Hora fin real  
        'original_date',     // Fecha original programada
        'observations',      // Observaciones de la clase
    ];

    /**
     * **Relación BELONGS_TO:** Instructor que dictó la clase.
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * **Relación BELONGS_TO:** Tipo de clase (Teórica/Práctica).
     */
    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id');
    }

    /**
     * **Relación BELONGS_TO:** Aula donde se dictó la clase.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    /**
     * **Relación BELONGS_TO:** Franja horaria de la clase real.
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    /**
     * **Relación BELONGS_TO:** Sesión de horario programada origen.
     */
    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class, 'schedule_session_id');
    }
    
    /**
     * **Relación HAS_MANY:** Asistencias tomadas en esta clase real.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'real_class_id');
    }
}
