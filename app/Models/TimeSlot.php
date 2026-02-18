<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Franja Horaria** (Mañana, Tarde, Noche).
 *
 * Define rangos horarios disponibles para programación.
 */
class TimeSlot extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'code',        // "MORNING", "AFTERNOON", "NIGHT"
        'name',        // "Mañana", "Tarde", "Noche"
        'start_time',  // "07:00:00"
        'end_time',    // "12:00:00"
    ];

    /**
     * **Relación HAS_MANY:** Clases reales en esta franja horaria.
     */
    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'time_slot_id');
    }
}
