<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Nivel de Formación** (Técnico, Tecnólogo, Profesional).
 *
 * Clasifica programas por nivel de cualificación SENA.
 */
class QualificationLevel extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Técnico", "Tecnólogo", "Profesional"
        'description', // Descripción del nivel
    ];

    /**
     * **Relación HAS_MANY:** Programas de formación de este nivel.
     */
    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }
}
