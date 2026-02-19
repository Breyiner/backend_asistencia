<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Modelo **Permiso** extendido de Spatie Permission.
 *
 * Personaliza permisos del sistema con campos adicionales.
 */
class Permission extends SpatiePermission
{
    /**
     * Campos permitidos para **mass assignment**.
     * 
     * **Extra:** description, display_name, group
     */
    protected $fillable = [
        'name',          // 'create-user', 'edit-ficha'
        'guard_name',    // 'web', 'api'
        'description',   // Descripción legible del permiso
        'display_name',  // Nombre mostrado en UI
        'group',         // 'Usuarios', 'Fichas', 'Horarios'
    ];
}
