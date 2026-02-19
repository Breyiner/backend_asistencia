<?php
   
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Estado Usuario** (Activo, Inactivo, Suspendido).
 *
 * Catálogo de estados para todos los usuarios del sistema.
 */
class UserStatus extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',        // "Activo", "Inactivo", "Suspendido"
        'description'  // Descripción del estado
    ];

    /**
     * **Relación HAS_MANY:** Usuarios con este estado.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
