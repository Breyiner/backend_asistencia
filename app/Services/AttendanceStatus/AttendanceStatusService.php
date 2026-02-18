<?php

namespace App\Services\AttendanceStatus;

use App\Events\ResourceChanged;
use App\Models\AttendanceStatus;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de estados de asistencia.
 *
 * Los estados de asistencia (presente, ausente, tardanza, etc.) son catálogos base
 * del sistema. Este servicio centraliza su CRUD sin necesidad de transacciones,
 * ya que cada operación afecta una sola tabla.
 */
class AttendanceStatusService
{
    /**
     * Retorna todos los estados de asistencia sin filtros ni paginación.
     *
     * Es un catálogo pequeño y estable, por lo que se trae completo con all().
     *
     * @return array
     */
    public function getAll()
    {
        $statuses = AttendanceStatus::all();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estados de asistencia obtenidos correctamente',
            'data' => $statuses
        ];
    }

    /**
     * Retorna un estado de asistencia por su ID.
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function getById($id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia obtenido correctamente',
            'data' => $status
        ];
    }

    /**
     * Crea un nuevo estado de asistencia.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create($data)
    {
        $status = AttendanceStatus::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            AttendanceStatus::class,
            $status->id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Estado de asistencia creado correctamente',
            'data' => $status,
        ];
    }

    /**
     * Actualiza un estado de asistencia existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID del estado.
     * @return array
     */
    public function update($data, $id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $statusData = [];

        if (array_key_exists('name', $data)) $statusData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $statusData['description'] = $data['description'];

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($statusData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $status->update($statusData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            AttendanceStatus::class,
            $status->id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia actualizado correctamente',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $status->fresh(),
        ];
    }

    /**
     * Elimina un estado de asistencia por su ID.
     *
     * El evento se dispara con el ID original ya que el modelo fue eliminado
     * y no puede ser referenciado después del delete().
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function delete($id)
    {
        $status = AttendanceStatus::find($id);

        if (!$status) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Estado de asistencia no encontrado',
            ];
        }

        $status->delete();

        // Se pasa $id y no $status->id porque el modelo ya no existe en BD tras el delete.
        event(new ResourceChanged(
            'eliminar',
            AttendanceStatus::class,
            $id,
            Auth::id(),
            'Estado de asistencia'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Estado de asistencia eliminado correctamente',
        ];
    }
}