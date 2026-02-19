<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Trimestre** académico (1er, 2do, 3er, 4to Trimestre).
 *
 * Periodos de la ficha de formación SENA.
 */
class Term extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',  // "Trimestre 1", "Trimestre 2", "Trimestre 3", "Trimestre 4"
    ];

    /**
     * **Relación HAS_MANY:** FichaTerm (ficha + trimestre).
     */
    public function fichaTerm()
    {
        return $this->hasMany(FichaTerm::class, 'term_id');
    }
}
