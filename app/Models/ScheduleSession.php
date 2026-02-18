<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Sesión de Horario** individual.
 *
 * Clase programada: Instructor + Día + Hora + Aula.
 */
class ScheduleSession extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'instructor_id',  // Instructor asignado
        'schedule_id',    // Horario padre (ficha+trimestre)
        'time_slot_id',   // Franja horaria (Mañana, Tarde)
        'classroom_id',   // Aula asignada
        'day_id',         // Día de la semana (Lunes=1)
        'start_time',     // HH:MM inicio clase
        'end_time',       // HH:MM fin clase
    ];

    /**
     * **Relación BELONGS_TO:** Instructor de la sesión.
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * **Relación BELONGS_TO:** Horario padre (contenedor).
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * **Relación BELONGS_TO:** Franja horaria de la sesión.
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * **Relación BELONGS_TO:** Aula de la sesión.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * **Relación BELONGS_TO:** Día de la semana.
     */
    public function day()
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * **Relación HAS_MANY:** Clases reales ejecutadas desde esta sesión.
     */
    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'schedule_session_id');
    }

    /**
     * **ACCESOR** duración en horas (end - start).
     * 
     * @return int Horas de duración
     */
    public function getDurationSessionAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInHours($end, false);
    }
}
