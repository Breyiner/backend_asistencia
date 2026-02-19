<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Tipo de Documento** (CC, TI, CE, etc).
 *
 * Catálogo de tipos de documento para usuarios del sistema.
 */
class DocumentType extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',     // "Cédula de Ciudadanía", "Tarjeta de Identidad"
        'acronym'   // "CC", "TI", "CE", "PA"
    ];

    /**
     * **Relación HAS_MANY:** Usuarios con este tipo de documento.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
