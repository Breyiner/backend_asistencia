<?php

namespace App\Http\Controllers\API\Notification;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador REST API para **Notificaciones de Usuario**.
 *
 * Inbox persistente con notificaciones en tiempo real (Echo/Broadcasting).
 * Estados: no leída / leída. Soporte para popover + página completa.
 *
 * **Endpoints clave**: unread-count (badge), latest (popover), index (página)
 *
 * @see NotificationService Consultas optimizadas y broadcasting
 */
class NotificationController extends Controller
{
    /**
     * Servicio de notificaciones.
     */
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Todas las notificaciones (solo admins).
     * GET /api/notifications/all
     *
     * Paginado para auditoría.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        $response = $this->notificationService->getAllNotifications($request->get('perPage', 10));

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
     * Notificaciones del usuario autenticado (página completa).
     * GET /api/notifications
     *
     * Filtros: status (all/unread/read), perPage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->notificationService->getNotificationsByUser(
            Auth::id(),
            $request->query('status', 'all'),
            $request->query('perPage', 10)
        );

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
     * Últimas notificaciones (popover campanita).
     * GET /api/notifications/latest
     *
     * Limit: 10-20, status: all/unread. Sin paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest(Request $request)
    {
        $response = $this->notificationService->latestByUser(
            Auth::id(),
            (int) $request->query('limit', 10),
            $request->query('status', 'all')
        );

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
     * Contador notificaciones no leídas (badge).
     * GET /api/notifications/unread-count
     *
     * Query optimizada COUNT(*).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        $response = $this->notificationService->unreadCount(Auth::id());

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
     * Detalle notificación (marca como leída automáticamente).
     * GET /api/notifications/{id}
     *
     * Valida pertenencia al usuario.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->notificationService->getNotificationByUser(Auth::id(), (int) $id);

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
     * Marcar notificación como leída.
     * PATCH /api/notifications/{id}/read
     *
     * Idempotente (ya leída no cambia nada).
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(string $id)
    {
        $response = $this->notificationService->markAsRead(Auth::id(), (int) $id);

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
     * Marcar todas como leídas.
     * PATCH /api/notifications/read-all
     *
     * Operación masiva optimizada.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $response = $this->notificationService->markAllAsRead(Auth::id());

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
     * Eliminar notificación del inbox.
     * DELETE /api/notifications/{id}
     *
     * Soft delete, valida pertenencia.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->notificationService->deleteFromInbox(Auth::id(), (int) $id);

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
