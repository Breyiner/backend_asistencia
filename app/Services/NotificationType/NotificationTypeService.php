<?php

namespace App\Services\NotificationType;

use App\Events\ResourceChanged;
use App\Models\NotificationType;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de tipos de notificación.
 *
 * Los tipos de notificación son un catálogo global (CRUD, SISTEMA, ALERTA, etc.)
 * que clasifican las notificaciones del sistema. No aplica RBAC ya que son
 * datos de configuración administrados únicamente por ADMIN.
 *
 * Cada tipo tiene un 'key' único usado para identificarlo programáticamente
 * desde los listeners y eventos del sistema.
 */
class NotificationTypeService
{
    /**
     * Retorna todos los tipos de notificación sin paginación.
     *
     * Se usa all() en lugar de paginate() porque es un catálogo pequeño y estático
     * que el frontend necesita completo para poblar selects y mapear tipos en la UI.
     *
     * @return array
     */
    public function getAll()
    {
        // all() es adecuado aquí: los tipos de notificación son pocos y rara vez cambian.
        $data = NotificationType::orderBy('name', 'asc')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipos de notificación obtenidos correctamente',
            'data' => $data,
        ];
    }

    /**
     * Retorna el detalle de un tipo de notificación por su ID.
     *
     * @param  mixed  $notificationTypeId  ID del tipo de notificación.
     * @return array
     */
    public function getById($notificationTypeId)
    {
        $data = NotificationType::find($notificationTypeId);

        if (!$data) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de notificación no encontrado',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de notificación obtenido correctamente',
            'data' => $data,
        ];
    }

    /**
     * Crea un nuevo tipo de notificación.
     *
     * El campo 'key' debe ser único en la tabla; la validación de unicidad
     * se delega al Request (StoreNotificationTypeRequest).
     *
     * @param  array  $data  Datos validados (name requerido, key único requerido).
     * @return array
     */
    public function store($data)
    {
        // Renombra la variable para no pisar el parámetro $data con el modelo creado.
        $created = NotificationType::create($data);

        event(new ResourceChanged(
            'crear',
            NotificationType::class,
            $created->id,
            Auth::id(),
            'Tipo de Notificación',
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Tipo de notificación creado correctamente',
            'data' => $created,
        ];
    }

    /**
     * Actualiza un tipo de notificación existente.
     *
     * Solo actualiza los campos presentes en $data. El evento se dispara
     * únicamente si hay campos que efectivamente cambiar.
     *
     * @param  mixed  $notificationTypeId  ID del tipo de notificación.
     * @param  array  $data                Campos a actualizar (name, key).
     * @return array
     */
    public function update($notificationTypeId, $data)
    {
        $notificationType = NotificationType::find($notificationTypeId);

        if (!$notificationType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de notificación no encontrado',
                'data' => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $dataToUpdate = [];

        if (array_key_exists('name', $data)) $dataToUpdate['name'] = $data['name'];
        if (array_key_exists('key', $data)) $dataToUpdate['key'] = $data['key'];

        // Solo ejecuta el update y el evento si hay campos que cambiar.
        if (!empty($dataToUpdate)) {
            $notificationType->update($dataToUpdate);

            event(new ResourceChanged(
                'actualizar',
                NotificationType::class,
                $notificationType->id,
                Auth::id(),
                'Tipo de Notificación',
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de notificación actualizado correctamente',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $notificationType->fresh(),
        ];
    }

    /**
     * Elimina un tipo de notificación por su ID.
     *
     * ⚠️ Precaución: eliminar un tipo en uso puede dejar notificaciones
     * existentes con notification_type_id huérfano si no hay FK con CASCADE.
     * Verificar restricciones de integridad referencial antes de permitirlo.
     *
     * @param  mixed  $notificationTypeId  ID del tipo de notificación.
     * @return array
     */
    public function destroy($notificationTypeId)
    {
        // 1) Buscar el tipo de notificación por ID.
        $notificationType = NotificationType::find($notificationTypeId);

        // 2) Validar existencia para responder 404 controlado.
        if (!$notificationType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de notificación no encontrado',
                'data' => [],
            ];
        }

        // 3) Validar integridad referencial (regla de negocio):
        //    Si el tipo ya está siendo usado por notificaciones existentes, NO se debe eliminar.
        //    exists() es más eficiente que count(): no cuenta todo, solo verifica si existe 1 registro.
        if ($notificationType->notifications()->exists()) {
            return [
                'error' => true,
                'code' => 409, // Conflicto: no se puede eliminar por dependencias existentes.
                'message' => 'No se puede eliminar este tipo de notificación porque tiene notificaciones asociadas',
                'data' => [],
            ];
        }

        // 4) Guardar el ID antes de eliminar para auditoría/evento.
        $deletedId = $notificationType->id;

        // 5) Eliminar solo si no hay dependencias.
        $notificationType->delete();

        // 6) Disparar evento con el ID eliminado.
        event(new ResourceChanged(
            'eliminar',
            NotificationType::class,
            $deletedId,
            Auth::id(),
            'Tipo de Notificación',
        ));

        // 7) Respuesta exitosa.
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de notificación eliminado correctamente',
            'data' => [],
        ];
    }
}
