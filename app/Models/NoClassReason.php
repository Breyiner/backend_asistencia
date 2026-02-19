<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Motivo Día sin Clase** (Festivo, Paro, Suspensión, etc).
 *
 * Catálogo de motivos para días no lectivos.
 */
class NoClassReason extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Festivo Nacional", "Paro Docente", "Suspensión"
        'description', // Descripción detallada del motivo
    ];

    /**
     * **Relación HAS_MANY:** Días sin clase con este motivo.
     */
    public function noClassDays()
    {
        return $this->hasMany(NoClassDay::class, 'reason_id');
    }
}
