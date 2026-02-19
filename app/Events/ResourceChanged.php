<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando un recurso cambia (CRUD).
 *
 * Este evento implementa ShouldDispatchAfterCommit, lo que significa
 * que se despacha DESPUÉS de que la transacción de base de datos
 * se haya confirmado (committed).
 *
 * Esto es importante porque:
 * - Garantiza que los datos ya están guardados en la BD
 * - Evita condiciones de carrera donde el listener intenta
 *   acceder a datos que aún no se han confirmado
 *
 * Casos de uso típicos:
 * - Auditoría: registrar cambios en un log de actividad
 * - Notificaciones: avisar a usuarios sobre cambios en recursos
 * - Sincronización: actualizar índices de búsqueda
 *
 * Uso:
 * event(new ResourceChanged(
 *     action: 'created',
 *     subjectType: 'User',
 *     subjectId: $user->id,
 *     actorUserId: auth()->id(),
 *     subjectLabel: $user->name
 * ));
 *
 * Listener (en EventServiceProvider):
 * ResourceChanged::class => [
 *     AuditResourceChange::class,
 *     NotifyAdminsOfChange::class,
 * ]
 */
class ResourceChanged implements ShouldDispatchAfterCommit
{
    // Permite disparar el evento desde cualquier lugar
    use Dispatchable;

    // Permite serializar modelos para colas
    use SerializesModels;

    /**
     * Crea una nueva instancia del evento.
     *
     * Usa constructor property promotion de PHP 8.
     *
     * @param string $action Acción realizada ('created', 'updated', 'deleted')
     * @param string $subjectType Tipo del recurso afectado (ej: 'User', 'Ficha', 'Apprentice')
     * @param int|string $subjectId ID del recurso afectado
     * @param int $actorUserId ID del usuario que realizó la acción
     * @param string|null $subjectLabel Etiqueta legible del recurso (ej: nombre del usuario)
     */
    public function __construct(
        public string $action,              // Tipo de acción: 'created' | 'updated' | 'deleted'
        public string $subjectType,         // Tipo del recurso: 'User', 'Ficha', etc.
        public int|string $subjectId,       // ID del recurso (int o string según el modelo)
        public int $actorUserId,            // ID del usuario que realizó la acción
        public ?string $subjectLabel = null // Etiqueta legible opcional del recurso
    ) {}
}