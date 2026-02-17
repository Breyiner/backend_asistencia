<?php

namespace App\Services\NotificationType;

use App\Events\ResourceChanged;
use App\Models\NotificationType;
use Illuminate\Support\Facades\Auth;

class NotificationTypeService
{
    public function getAll()
    {
        $data = NotificationType::all();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipos de notificación obtenidas correctamente',
            'data' => $data,
        ];
    }

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

    public function store($data)
    {
        $data = NotificationType::create($data);

        event(new ResourceChanged(
            'crear',
            NotificationType::class,
            $data->id,
            Auth::id(),
            'Tipo de Notificación',
        ));


        return [
            'error' => false,
            'code' => 201,
            'message' => 'Tipo de notificación creado correctamente',
            'data' => $data,
        ];
    }

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

        $dataToUpdate = [];

        if (array_key_exists('name', $data)) {
            $dataToUpdate['name'] = $data['name'];
        }

        if (array_key_exists('key', $data)) {
            $dataToUpdate['key'] = $data['key'];
        }

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
            'data' => $notificationType->fresh(),
        ];
    }

    public function destroy($notificationTypeId)
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

        $notificationType->delete();

        event(new ResourceChanged(
            'eliminar',
            NotificationType::class,
            $notificationTypeId,
            Auth::id(),
            'Tipo de Notificación',
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de notificación eliminado correctamente',
            'data' => [],
        ];
    }
}
