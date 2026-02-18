<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Trimestre/Fase de Ficha**.
 *
 * Combina Ficha + Trimestre + Fase con periodo de fechas.
 */
class FichaTerm extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'term_id',     // Trimestre (1er, 2do, 3er, 4to)
        'ficha_id',    // Ficha SENA asociada
        'phase_id',    // Fase específica del trimestre
        'start_date',  // Inicio periodo trimestre
        'end_date',    // Fin periodo trimestre
        'is_current'   // TRUE si es el trimestre activo
    ];

    /**
     * **CASTS** tipos de datos.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'
    ];

    /**
     * **Relación BELONGS_TO:** Ficha padre de este trimestre.
     */
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }

    /**
     * **Relación BELONGS_TO:** Trimestre (Trimestre 1, 2, 3, 4).
     */
    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * **Relación BELONGS_TO:** Fase específica del trimestre.
     */
    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }

    /**
     * **Relación HAS_ONE:** Horario asociado a este trimestre/ficha.
     */
    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'ficha_term_id');
    }
}
