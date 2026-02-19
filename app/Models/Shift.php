<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Jornada** SENA (Diurna/Nocturna).
 *
 * Define horario general de fichas (mañana/tarde vs tarde/noche).
 */
class Shift extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',  // "Diurna" (1), "Nocturna" (2)
    ];

    /**
     * **Relación HAS_MANY:** Fichas de esta jornada.
     */
    public function fichas()
    {
        return $this->hasMany(Ficha::class);
    }
}
