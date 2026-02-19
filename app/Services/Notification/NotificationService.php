<?php

namespace App\Services\Notification;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lógica de negocio para la gestión de notificaciones.
 *
 * Maneja la creación, distribución y gestión del inbox por usuario.
 * Las notificaciones son polimórficas (modelable_type/modelable_id) y multi-rol:
 * un mismo usuario puede recibir notificaciones distintas según su rol activo.
 *
 * El sistema tiene dos capas:
 * - Tabla notifications: la notificación global (título, contenido, tipo).
 * - Pivot notification_user: la relación usuario-notificación (leída, rol).
 */
class NotificationService
{
    /**
     * Obtiene el role_code del rol activo desde los atributos del request.
     *
     * Se inyecta en el middleware de autenticación como acting_role_code.
     *
     * @return string|null
     */
    private function actingRoleCode(): ?string
    {
        return request()->attributes->get('acting_role_code');
    }

    /**
     * Valida que exista un rol activo en el request.
     *
     * Usado como guard en todos los métodos que operan sobre el inbox del usuario,
     * ya que el pivot filtra notificaciones por role_code.
     *
     * @return array|null  Array de error si no hay role_code; null si es válido.
     */
    private function requireActingRoleCode(): ?array
    {
        $roleCode = $this->actingRoleCode();

        if (!$roleCode) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'Rol activo no enviado.',
                'data' => [],
            ];
        }

        return null;
    }

    /**
     * Crea una notificación, la asigna a múltiples usuarios y la emite por WebSocket.
     *
     * Ejecuta todo en una transacción para garantizar consistencia:
     * si el broadcast falla, el create y el sync se revierten.
     * El pivot almacena read_at (null = no leída) y role_code para filtrado por rol.
     *
     * @param  array  $userIds  IDs de los usuarios destinatarios.
     * @param  array  $data     Datos de la notificación:
     *                          - notification_type_id (requerido)
     *                          - title (requerido)
     *                          - content (requerido)
     *                          - role_code (requerido)
     *                          - modelable_type / modelable_id (opcionales, polimórfico)
     * @return array
     */
    public function notifyUsers(array $userIds, array $data)
    {
        $roleCode = $data['role_code'] ?? null;

        // Sin destinatarios no hay nada que crear; retorna éxito silencioso.
        if (empty($userIds)) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'Sin destinatarios, no se envió notificación.',
                'data' => null,
            ];
        }

        // role_code es obligatorio para saber en qué "inbox de rol" aparece la notificación.
        if (!$roleCode) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'role_code es requerido para asignar notificaciones por rol.',
                'data' => null,
            ];
        }

        // Transacción atómica: create + syncWithoutDetaching + broadcast deben ir juntos.
        $notification = DB::transaction(function () use ($userIds, $data, $roleCode) {

            // Crea el registro global de la notificación.
            $notification = Notification::create([
                'notification_type_id' => $data['notification_type_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                // Polymorphic: permite vincular la notificación a cualquier modelo (Ficha, Apprentice, etc.).
                'modelable_type' => $data['modelable_type'] ?? null,
                'modelable_id' => $data['modelable_id'] ?? null,
            ]);

            // Construye el payload del pivot: cada usuario con su estado inicial (no leída).
            $payload = [];
            foreach ($userIds as $id) {
                $payload[$id] = [
                    'read_at' => null,
                    'role_code' => $roleCode,
                ];
            }

            // syncWithoutDetaching: no elimina asignaciones previas de otros roles al mismo usuario.
            $notification->users()->syncWithoutDetaching($payload);

            // Emite el evento a los canales WebSocket de cada usuario (Laravel Reverb).
            broadcast(new NotificationCreated($notification, $userIds, $roleCode));

            return $notification;
        });

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Notificación creada y emitida.',
            'data' => $notification,
        ];
    }

    /**
     * Retorna una lista paginada de TODAS las notificaciones globales.
     *
     * Solo para uso administrativo (vista global sin filtro por usuario ni rol).
     * No incluye datos del pivot (read_at, role_code).
     *
     * @param  int|null  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAllNotifications(?int $perPage = null)
    {
        // latest() equivale a orderBy('created_at', 'desc').
        $query = Notification::latest();
        $data = $perPage ? $query->paginate($perPage) : $query->paginate(10);

        if ($data->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay notificaciones registradas',
                'data' => [],
                'paginate' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
            ];
        }

        // Transforma a array plano con fecha humanizada para el frontend.
        $items = $data->getCollection()->map(function ($n) {
            // diffForHumans() retorna "hace 5 minutos", "hace 2 días", etc.
            $humanized = Carbon::parse($n->created_at)->diffForHumans();

            return [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'type' => $n->notification_type_id,
                'created_at' => $n->created_at,
                'created_at_human' => $humanized,
            ];
        });

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificaciones obtenidas correctamente',
            'data' => $items,
            'paginate' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ];
    }

    /**
     * Retorna notificaciones paginadas de un usuario filtradas por rol y estado.
     *
     * Para la vista "Ver todas mis notificaciones". Filtra por el rol activo del
     * usuario para mostrar solo las notificaciones relevantes en su contexto actual.
     *
     * @param  mixed     $userId   ID del usuario.
     * @param  string    $status   'all' | 'unread' | 'read'.
     * @param  int|null  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getNotificationsByUser($userId, $status = 'all', ?int $perPage = null)
    {
        // Guard: role_code requerido para filtrar el pivot correctamente.
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        // Base query: notificaciones del usuario para el rol activo, más recientes primero.
        $query = $user->notifications()
            ->latest()
            ->wherePivot('role_code', $roleCode);

        // Filtro por estado de lectura sobre el pivot.
        if ($status === 'unread') $query->wherePivotNull('read_at');
        elseif ($status === 'read') $query->wherePivotNotNull('read_at');

        $data = $perPage ? $query->paginate($perPage) : $query->paginate(10);

        // Transforma incluyendo read_at del pivot para que el frontend pueda resaltar no leídas.
        $items = $data->getCollection()->map(function ($n) {
            $humanized = Carbon::parse($n->created_at)->diffForHumans();

            return [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'type' => $n->notification_type_id,
                'read_at' => $n->pivot?->read_at,
                'created_at' => $n->created_at,
                'created_at_human' => $humanized,
            ];
        });

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificaciones obtenidas correctamente',
            'data' => $items,
            'paginate' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ];
    }

    /**
     * Retorna las últimas N notificaciones de un usuario para el popover/campanita.
     *
     * A diferencia de getNotificationsByUser(), NO usa paginación: retorna una
     * colección simple limitada por $limit. Ideal para dropdowns de notificaciones.
     *
     * @param  int     $userId  ID del usuario.
     * @param  int     $limit   Máximo de notificaciones a retornar (default: 10).
     * @param  string  $status  'all' | 'unread' | 'read'.
     * @return array
     */
    public function latestByUser(int $userId, int $limit = 10, string $status = 'all')
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        $query = $user->notifications()
            ->latest()
            ->wherePivot('role_code', $roleCode);

        if ($status === 'unread') $query->wherePivotNull('read_at');
        elseif ($status === 'read') $query->wherePivotNotNull('read_at');

        // limit() sin paginate(): trae exactamente N registros, sin metadata de paginación.
        $data = $query->limit($limit)->get();

        $items = $data->map(function ($n) {
            $humanized = Carbon::parse($n->created_at)->diffForHumans();

            return [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'type' => $n->notification_type_id,
                'read_at' => $n->pivot?->read_at,
                'created_at' => $n->created_at,
                'created_at_human' => $humanized,
            ];
        });

        return ['error' => false, 'code' => 200, 'message' => 'Notificaciones obtenidas correctamente', 'data' => $items];
    }

    /**
     * Retorna el conteo de notificaciones no leídas del usuario en su rol activo.
     *
     * Para el badge numérico de la campanita. Usa count() directo sin traer modelos
     * completos; es la operación más eficiente del servicio.
     *
     * @param  int  $userId  ID del usuario.
     * @return array  data.count: número de notificaciones no leídas.
     */
    public function unreadCount(int $userId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            // Retorna count: 0 para que el frontend no rompa al intentar mostrar el badge.
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => ['count' => 0]];
        }

        // count() sobre el pivot: no carga los modelos Notification, solo cuenta filas.
        $count = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->wherePivotNull('read_at')
            ->count();

        return ['error' => false, 'code' => 200, 'message' => 'Conteo obtenido correctamente', 'data' => ['count' => $count]];
    }

    /**
     * Retorna el detalle de una notificación específica del inbox del usuario.
     *
     * Verifica que la notificación exista Y que pertenezca al usuario con su rol activo.
     * Evita que un usuario acceda a notificaciones de otro rol suyo o de otro usuario.
     *
     * @param  mixed  $userId          ID del usuario.
     * @param  mixed  $notificationId  ID de la notificación.
     * @return array
     */
    public function getNotificationByUser($userId, $notificationId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        // Filtra por role_code del pivot Y por ID de notificación simultáneamente.
        $notification = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->where('notifications.id', $notificationId)
            ->first();

        if (!$notification) {
            return ['error' => true, 'code' => 404, 'message' => 'Notificación no encontrada', 'data' => []];
        }

        $humanized = Carbon::parse($notification->created_at)->diffForHumans();

        $data = [
            'id' => $notification->id,
            'title' => $notification->title,
            'content' => $notification->content,
            'type' => $notification->notification_type_id,
            'created_at' => $notification->created_at,
            'created_at_human' => $humanized,
        ];

        return ['error' => false, 'code' => 200, 'message' => 'Notificación obtenida correctamente', 'data' => $data];
    }

    /**
     * Marca una notificación específica como leída actualizando el pivot.
     *
     * Flujo: verifica existencia → updateExistingPivot → re-consulta para devolver
     * el registro actualizado. La re-consulta garantiza que read_at refleje lo persistido.
     *
     * @param  mixed  $userId          ID del usuario.
     * @param  mixed  $notificationId  ID de la notificación.
     * @return array
     */
    public function markAsRead($userId, $notificationId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        // Primero verifica existencia con exists() antes de hacer el update (evita update silencioso).
        $exists = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->where('notifications.id', $notificationId)
            ->exists();

        if (!$exists) {
            return ['error' => true, 'code' => 404, 'message' => 'Notificación no encontrada', 'data' => []];
        }

        // updateExistingPivot: modifica solo el read_at del pivot sin afectar el modelo global.
        $user->notifications()->updateExistingPivot($notificationId, [
            'read_at' => Carbon::now(),
        ]);

        // Re-consulta para retornar el estado actualizado (con read_at persistido).
        $data = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->where('notifications.id', $notificationId)
            ->first();

        $humanized = Carbon::parse($data->created_at)->diffForHumans();

        $result = [
            'id' => $data->id,
            'title' => $data->title,
            'content' => $data->content,
            'type' => $data->notification_type_id,
            'created_at' => $data->created_at,
            'created_at_human' => $humanized,
        ];

        return ['error' => false, 'code' => 200, 'message' => 'Notificación marcada como leída correctamente', 'data' => $result];
    }

    /**
     * Marca TODAS las notificaciones no leídas del usuario como leídas en su rol activo.
     *
     * Usa newPivotQuery() para ejecutar un UPDATE masivo directo sobre la tabla pivot,
     * evitando cargar cada modelo individualmente (más eficiente que iterar con markAsRead).
     *
     * @param  mixed  $userId  ID del usuario.
     * @return array
     */
    public function markAllAsRead($userId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        $now = Carbon::now();

        // newPivotQuery(): accede directamente al query builder de la tabla pivot.
        // Es el método más eficiente para UPDATE masivo sin cargar modelos en memoria.
        $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->wherePivotNull('read_at')
            ->newPivotQuery()
            ->update(['read_at' => $now]);

        return ['error' => false, 'code' => 200, 'message' => 'Notificaciones marcadas como leídas correctamente', 'data' => []];
    }

    /**
     * Elimina una notificación del inbox del usuario (solo el registro pivot).
     *
     * detach() elimina la fila en notification_user, NO el modelo global Notification.
     * La notificación puede seguir existiendo en el inbox de otros usuarios.
     *
     * @param  mixed  $userId          ID del usuario.
     * @param  mixed  $notificationId  ID de la notificación.
     * @return array
     */
    public function deleteFromInbox($userId, $notificationId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        $exists = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->where('notifications.id', $notificationId)
            ->exists();

        if (!$exists) {
            return ['error' => true, 'code' => 404, 'message' => 'Notificación no encontrada', 'data' => []];
        }

        // detach() solo elimina la fila del pivot; el registro en notifications permanece intacto.
        $user->notifications()->detach($notificationId);

        return ['error' => false, 'code' => 200, 'message' => 'Notificación eliminada correctamente', 'data' => []];
    }
}

