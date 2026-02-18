<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Tipo de Clase** (Teórica, Práctica, Laboratorio, etc).
 *
 * Clasifica el tipo de clase real programada.
 */
class ClassType extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // Tipo: "Teórica", "Práctica", "Laboratorio"
        'description'  // Descripción del tipo de clase
    ];

    /**
     * **Relación HAS_MANY:** Clases reales de este tipo.
     */
    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'class_type_id');
    }
}
