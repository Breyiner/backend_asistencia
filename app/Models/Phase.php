<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Fase/Etapa** de formación.
 *
 * Define fases específicas dentro de trimestres (Fase 1, Fase 2, etc).
 */
class Phase extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Fase 1", "Fase 2", "Etapa Práctica"
        'description'  // Descripción de la fase
    ];

    /**
     * **Relación HAS_MANY:** FichaTerm (ficha+trimestre) de esta fase.
     * 
     */
    public function fichaTerm()
    {
        return $this->hasMany(FichaTerm::class, 'phase_id');
    }
}
