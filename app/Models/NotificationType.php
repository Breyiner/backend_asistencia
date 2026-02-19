<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Tipo de Notificación** (Asistencia, Horario, Ficha, etc).
 *
 * Catálogo de tipos para clasificar notificaciones del sistema.
 */
class NotificationType extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',  // "Asistencia", "Horario", "Ficha Nueva"
        'key',   // "attendance", "schedule", "new_ficha"
    ];

    /**
     * **Relación HAS_MANY:** Notificaciones de este tipo.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
