<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Aula/Ambiente** físico.
 *
 * Registra aulas disponibles para clases reales y sesiones de horario.
 */
class Classroom extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // Nombre del aula: "Aula 101", "Laboratorio 3"
        'description', // Descripción/capacidad del ambiente
    ];

    /**
     * **Relación HAS_MANY:** Sesiones de horario programadas en este aula.
     */
    public function scheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class);
    }

    /**
     * **Relación HAS_MANY:** Clases reales ejecutadas en este aula.
     */
    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'classroom_id');
    }
}
