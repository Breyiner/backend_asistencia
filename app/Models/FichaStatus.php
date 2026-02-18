<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Estado de Ficha SENA** (Activa, Finalizada, Suspendida, etc).
 *
 * Catálogo de estados para fichas de formación.
 */
class FichaStatus extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Activa", "Finalizada", "Suspendida"
        'description', // Descripción del estado de ficha
    ];

    /**
     * **Relación HAS_MANY:** Fichas con este estado.
     */
    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'status_id');
    }
}
