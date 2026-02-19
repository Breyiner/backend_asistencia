<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Estado de Asistencia** (Presente, Tardanza, Ausente, etc).
 *
 * Catálogo de estados para registrar asistencias.
 */
class AttendanceStatus extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'code',        // Códigos únicos: 'PRESENT', 'LATE', 'ABSENT'
        'name',        // Nombres legibles: 'Presente', 'Tardanza', 'Ausente'
        'description', // Descripción detallada del estado
    ];

    /**
     * **Relación HAS_MANY:** Asistencias relacionadas.
     */

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
