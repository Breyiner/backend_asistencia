<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Notificación** del sistema.
 *
 * Soporta **polimorfismo** (notificaciones para cualquier modelo).
 */
class Notification extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'notification_type_id', // Tipo de notificación
        'title',                // Título corto
        'content',              // Contenido/mensaje
        'modelable_type',       // Morph: Tipo modelo relacionado
        'modelable_id',         // Morph: ID modelo relacionado
    ];

    /**
     * **CASTS** timestamps.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * **Relación BELONGS_TO:** Tipo de notificación.
     */
    public function type()
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    /**
     * **Relación MORPH_TO:** Modelo polimórfico relacionado.
     * 
     * Ej: Ficha, Aprendiz, Clase, etc.
     */
    public function modelable()
    {
        return $this->morphTo();
    }

    /**
     * **Relación MANY-TO-MANY:** Usuarios que reciben la notificación.
     * 
     * Pivot `notification_user` con `read_at` y timestamps.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }
}
