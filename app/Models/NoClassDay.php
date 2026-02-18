<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Día sin Clase** por ficha.
 *
 * Registra días no lectivos (festivos, suspensiones, etc).
 */
class NoClassDay extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'ficha_id',     // Ficha afectada
        'date',         // Fecha del día sin clase
        'reason_id',    // Motivo (festivo, paro, etc)
        'observations'  // Observaciones adicionales
    ];

    /**
     * **Relación BELONGS_TO:** Motivo del día sin clase.
     */
    public function reason()
    {
        return $this->belongsTo(NoClassReason::class, 'reason_id');
    }

    /**
     * **Relación BELONGS_TO:** Ficha donde aplica el día sin clase.
     */
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }
}
