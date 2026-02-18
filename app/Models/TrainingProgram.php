<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Programa de Formación** SENA.
 *
 * Técnico/Tecnólogo con área, nivel y coordinador.
 */
class TrainingProgram extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',                  // "Técnico Desarrollo Software"
        'description',           // Descripción programa
        'duration',              // Horas totales programa
        'qualification_level_id', // Técnico/Tecnólogo
        'area_id',               // Área formación (Informática, etc)
        'coordinator_id'         // Coordinador programa
    ];

    /**
     * **Relación BELONGS_TO:** Nivel de cualificación.
     */
    public function qualificationLevel()
    {
        return $this->belongsTo(QualificationLevel::class);
    }

    /**
     * **Relación BELONGS_TO:** Área de formación.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * **Relación BELONGS_TO:** Coordinador del programa.
     */
    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /**
     * **Relación HAS_MANY:** Fichas creadas de este programa.
     */
    public function fichas()
    {
        return $this->hasMany(Ficha::class);
    }

    /**
     * **Relación HAS_MANY_THROUGH:** Aprendices indirectos.
     * 
     * Programa → Fichas → Aprendices
     */
    public function apprentices()
    {
        return $this->hasManyThrough(
            Apprentice::class,
            Ficha::class,
            'training_program_id',  // Local key en Ficha
            'ficha_id',             // Foreign key en Apprentice
            'id',                   // Local key en TrainingProgram
            'id'                    // Local key en Ficha
        );
    }
}
