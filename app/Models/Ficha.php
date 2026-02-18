<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Ficha SENA** grupo de aprendices.
 *
 * Representa una ficha de formación completa con trimestres.
 */
class Ficha extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'gestor_id',              // Coordinador/gestor de la ficha
        'shift_id',               // Jornada: Diurna/Nocturna
        'ficha_number',           // Número identificador ficha
        'start_date',             // Fecha inicio ficha
        'end_date',               // Fecha fin ficha
        'training_program_id',    // Programa de formación asociado
        'status_id',              // Estado ficha (Activa, Finalizada)
    ];

    /**
     * **CASTS** tipos de datos fechas.
     */
    public function casts()
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * **Relación BELONGS_TO:** Gestor/coordinador de la ficha.
     */
    public function gestor()
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    /**
     * **Relación BELONGS_TO:** Jornada (Diurna/Nocturna).
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * **Relación HAS_MANY:** Aprendices inscritos en la ficha.
     */
    public function apprentices()
    {
        return $this->hasMany(User::class, 'ficha_id');
    }

    /**
     * **Relación BELONGS_TO:** Programa de formación de la ficha.
     */
    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    /**
     * **Relación BELONGS_TO:** Estado de la ficha.
     */
    public function status()
    {
        return $this->belongsTo(FichaStatus::class, 'status_id');
    }

    /**
     * **Relación HAS_MANY:** Trimestres (periodos) de la ficha.
     */
    public function fichaTerms()
    {
        return $this->hasMany(FichaTerm::class, 'ficha_id');
    }

    /**
     * **Relación HAS_ONE:** Trimestre actual (is_current = true).
     */
    public function currentFichaTerm()
    {
        return $this->hasOne(FichaTerm::class, 'ficha_id')->where('is_current', true);
    }
}
