<?php

namespace App\Services\Notification;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    private function actingRoleCode(): ?string
    {
        return request()->attributes->get('acting_role_code');
    }

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
     * Crea notificación, asigna a usuarios con role_code y emite por WebSocket.
     */
    public function notifyUsers(array $userIds, array $data)
    {
        $roleCode = $data['role_code'] ?? null;

        if (empty($userIds)) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'Sin destinatarios, no se envió notificación.',
                'data' => null,
            ];
        }

        if (!$roleCode) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'role_code es requerido para asignar notificaciones por rol.',
                'data' => null,
            ];
        }

        $notification = DB::transaction(function () use ($userIds, $data, $roleCode) {
            $notification = Notification::create([
                'notification_type_id' => $data['notification_type_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'modelable_type' => $data['modelable_type'] ?? null,
                'modelable_id' => $data['modelable_id'] ?? null,
            ]);

            $payload = [];
            foreach ($userIds as $id) {
                $payload[$id] = [
                    'read_at' => null,
                    'role_code' => $roleCode,
                ];
            }

            $notification->users()->syncWithoutDetaching($payload);

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
     * Listado general de TODAS las notificaciones (solo paginado).
     */
    public function getAllNotifications(?int $perPage = null)
    {
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

        $items = $data->getCollection()->map(function ($n) {
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
     * Listado por usuario (para “Ver todas”): paginado, con status.
     */
    public function getNotificationsByUser($userId, $status = 'all', ?int $perPage = null)
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

        $data = $perPage ? $query->paginate($perPage) : $query->paginate(10);

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
     * Para el popover: últimas N (sin paginación).
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
     * Conteo de no leídas para la campanita.
     */
    public function unreadCount(int $userId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => ['count' => 0]];
        }

        $count = $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->wherePivotNull('read_at')
            ->count();

        return ['error' => false, 'code' => 200, 'message' => 'Conteo obtenido correctamente', 'data' => ['count' => $count]];
    }

    /**
     * Obtener una notificación específica por usuario.
     */
    public function getNotificationByUser($userId, $notificationId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

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

    public function markAsRead($userId, $notificationId)
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

        $user->notifications()->updateExistingPivot($notificationId, [
            'read_at' => Carbon::now(),
        ]);

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

    public function markAllAsRead($userId)
    {
        if ($err = $this->requireActingRoleCode()) return $err;
        $roleCode = $this->actingRoleCode();

        $user = User::find($userId);
        if (!$user) {
            return ['error' => true, 'code' => 404, 'message' => 'Usuario no encontrado', 'data' => []];
        }

        $now = Carbon::now();

        $user->notifications()
            ->wherePivot('role_code', $roleCode)
            ->wherePivotNull('read_at')
            ->newPivotQuery()
            ->update(['read_at' => $now]);

        return ['error' => false, 'code' => 200, 'message' => 'Notificaciones marcadas como leídas correctamente', 'data' => []];
    }

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

        $user->notifications()->detach($notificationId);

        return ['error' => false, 'code' => 200, 'message' => 'Notificación eliminada correctamente', 'data' => []];
    }
}