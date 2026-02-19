<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Día de la semana** (Lunes=1, Martes=2, ..., Domingo=7).
 *
 * Define los días disponibles para programación de horarios.
 */
class Day extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Lunes", "Martes", "Miércoles", etc
        'day_number'   // 1=Lunes, 2=Martes, ..., 7=Domingo
    ];

    /**
     * **CASTS** tipos de datos.
     */
    protected $casts = [
        'day_number' => 'integer',
    ];

    /**
     * **Relación HAS_MANY:** Sesiones de horario programadas en este día.
     */
    public function scheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class);
    }
}
