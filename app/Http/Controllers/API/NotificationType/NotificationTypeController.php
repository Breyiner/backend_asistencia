<?php

namespace App\Http\Controllers\API\NotificationType;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotifcationType\StoreNotificationTypeRequest;
use App\Http\Requests\NotifcationType\UpdateNotificationTypeRequest;
use App\Services\NotificationType\NotificationTypeService;

/**
 * Controlador REST API para **Tipos de Notificaciones**.
 *
 * Catálogo centralizado: plantillas `{variable}`, canales (app/email/sms), prioridades, target_roles.
 * Tipos sistema protegidos (is_system=true). Cacheable, sin paginación (10-20 tipos).
 *
 * **Endpoints clave**: index (configuración), show (estadísticas), CRUD admins.
 *
 * @see NotificationTypeService Validaciones plantillas/canales/unicidad
 * @see StoreNotificationTypeRequest / UpdateNotificationTypeRequest Validaciones estrictas
 */
class NotificationTypeController extends Controller
{
    /**
     * Servicio de tipos de notificaciones.
     */
    protected NotificationTypeService $notificationTypeService;

    public function __construct(NotificationTypeService $notificationTypeService)
    {
        $this->notificationTypeService = $notificationTypeService;
    }

    /**
     * Lista todos los tipos (configuración sistema).
     * GET /api/notification-types
     *
     * Filtros: priority, target_role, channel, active, is_system. Orden: system→priority→name.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $response = $this->notificationTypeService->getAll();

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
     * Detalle tipo con estadísticas.
     * GET /api/notification-types/{id}
     *
     * Incluye: variables disponibles, stats (sent/open_rate), recent_notifications.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $response = $this->notificationTypeService->getById($id);

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
     * Crea tipo personalizado (admins).
     * POST /api/notification-types
     *
     * Valida: code único (UPPER_CASE), plantillas `{var}`, canales válidos, roles existentes.
     *
     * @param StoreNotificationTypeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreNotificationTypeRequest $request)
    {
        $response = $this->notificationTypeService->store($request->validated());

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
     * Actualiza tipo (no code en is_system).
     * PUT /api/notification-types/{id}
     *
     * Invalida cache. Cambios afectan futuras notificaciones.
     *
     * @param UpdateNotificationTypeRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNotificationTypeRequest $request, string $id)
    {
        $response = $this->notificationTypeService->update($request->validated(), $id);

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
     * Elimina tipo personalizado sin uso.
     * DELETE /api/notification-types/{id}
     *
     * Bloquea: is_system=true, notifications_sent>0. Sugiere active=false.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $response = $this->notificationTypeService->destroy($id);

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
