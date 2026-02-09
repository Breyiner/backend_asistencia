<?php

namespace App\Http\Controllers\API\Notification;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Listado general de TODAS las notificaciones (solo paginado).
     */
    public function all(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $response = $this->notificationService->getAllNotifications($perPage);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? []
        );
    }

    /**
     * Listado por usuario (para “Ver todas”): paginado, con status.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $status = $request->query('status', 'all');
        $perPage = $request->query('perPage', 10);

        $response = $this->notificationService->getNotificationsByUser($userId, $status, $perPage);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? [],
            $response['paginate'] ?? []
        );
    }

    /**
     * Para el popover: últimas N (sin paginación).
     */
    public function latest(Request $request)
    {
        $userId = Auth::id();
        $limit = (int) $request->query('limit', 10);
        $status = $request->query('status', 'all');

        $response = $this->notificationService->latestByUser($userId, $limit, $status);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    /**
     * Conteo de no leídas para la campanita.
     */
    public function unreadCount()
    {
        $userId = Auth::id();

        $response = $this->notificationService->unreadCount($userId);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    public function show(string $notification_id)
    {
        $userId = Auth::id();

        $response = $this->notificationService->getNotificationByUser($userId, (int) $notification_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    public function markAsRead(string $notification_id)
    {
        $userId = Auth::id();

        $response = $this->notificationService->markAsRead($userId, (int) $notification_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    public function markAllAsRead()
    {
        $userId = Auth::id();

        $response = $this->notificationService->markAllAsRead($userId);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }

    public function destroy(string $notification_id)
    {
        $userId = Auth::id();

        $response = $this->notificationService->deleteFromInbox($userId, (int) $notification_id);

        if ($response['error']) {
            return ResponseFormatter::error($response['message'], $response['code']);
        }

        return ResponseFormatter::success(
            $response['message'],
            $response['code'],
            $response['data'] ?? []
        );
    }
}