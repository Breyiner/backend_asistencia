<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Perfil Usuario** básico.
 *
 * Datos personales compartidos entre todos los tipos de usuario.
 */
class UserProfile extends Model
{
    /**
     * Tabla **personalizada** profiles.
     */
    protected $table = 'profiles';

    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'user_id',         // Usuario propietario
        'first_name',      // Primer nombre
        'last_name',       // Apellido
        'telephone_number' // Teléfono contacto
    ];

    /**
     * **Relación BELONGS_TO:** Usuario dueño del perfil.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
