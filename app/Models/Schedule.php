<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo **Horario** por trimestre de ficha.
 *
 * Contenedor de sesiones de horario (clases programadas).
 */
class Schedule extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'description',     // Descripción opcional del horario
        'ficha_term_id',   // FichaTerm (ficha+trimestre) propietario
    ];

    /**
     * **Relación BELONGS_TO:** FichaTerm (ficha+trimestre) del horario.
     * 
     * **Eager load:** ficha y term para acceso rápido.
     */
    public function fichaTerm(): BelongsTo
    {
        return $this->belongsTo(FichaTerm::class, 'ficha_term_id')->with(['ficha', 'term']);
    }

    /**
     * **Relación HAS_MANY:** Sesiones individuales del horario.
     */
    public function scheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class);
    }
}
