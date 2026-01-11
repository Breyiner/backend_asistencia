<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    public function getAllNotifications()
    {
        $data = Notification::latest()->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificaciones obtenidas correctamente',
            'data' => $data,
        ];
    }

    public function getNotificationsByUser($userId, $status = 'all')
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Usuario no encontrado',
                'data' => [],
            ];
        }

        $query = $user->notifications()->latest();

        if ($status === 'unread') {
            $query->wherePivotNull('read_at');
        } elseif ($status === 'read') {
            $query->wherePivotNotNull('read_at');
        }

        $data = $query->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificaciones obtenidas correctamente',
            'data' => $data,
        ];
    }

    public function getNotificationByUser($userId, $notificationId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Usuario no encontrado',
                'data' => [],
            ];
        }

        $notification = $user->notifications()
            ->where('notifications.id', $notificationId)
            ->first();

        if (!$notification) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Notificación no encontrada',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificación obtenida correctamente',
            'data' => $notification,
        ];
    }

    public function markAsRead($userId, $notificationId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Usuario no encontrado',
                'data' => [],
            ];
        }

        $exists = $user->notifications()
            ->where('notifications.id', $notificationId)
            ->exists();

        if (!$exists) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Notificación no encontrada',
                'data' => [],
            ];
        }

        $user->notifications()->updateExistingPivot($notificationId, [
            'read_at' => Carbon::now(),
        ]);

        $data = $user->notifications()
            ->where('notifications.id', $notificationId)
            ->first();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificación marcada como leída correctamente',
            'data' => $data,
        ];
    }

    public function markAllAsRead($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Usuario no encontrado',
                'data' => [],
            ];
        }

        $now = Carbon::now();

        $user->notifications()
            ->wherePivotNull('read_at')
            ->newPivotQuery()
            ->update(['read_at' => $now]);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificaciones marcadas como leídas correctamente',
            'data' => [],
        ];
    }

    public function deleteFromInbox($userId, $notificationId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Usuario no encontrado',
                'data' => [],
            ];
        }

        $exists = $user->notifications()
            ->where('notifications.id', $notificationId)
            ->exists();

        if (!$exists) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Notificación no encontrada',
                'data' => [],
            ];
        }

        $user->notifications()->detach($notificationId);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Notificación eliminada correctamente',
            'data' => [],
        ];
    }
}
