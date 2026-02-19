<?php

namespace App\Services\FichaStatus;

use App\Events\ResourceChanged;
use App\Models\FichaStatus;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de estados de ficha.
 *
 * Los estados de ficha (activa, inactiva, etc.) son un catálogo base usado por las fichas.
 * Incluye protección de integridad referencial en el delete para evitar eliminar
 * estados que ya estén asignados a fichas existentes.
 */
class FichaStatusService
{
    /**
     * Retorna todos los estados de ficha.
     *
     * Método estático porque no requiere estado de instancia; se puede llamar
     * directamente sin instanciar el servicio.
     * Retorna mensaje diferenciado si el catálogo está vacío.
     *
     * @return array
     */
    public static function getAll()
    {
        $statuses = FichaStatus::all();

        // Retorna un mensaje diferenciado si el catálogo está vacío,
        // sin lanzar error ya que es un estado válido del sistema.
        if (count($statuses) == 0) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay estados registrados",
                "data" => $statuses
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estados obtenidos con éxito",
            "data" => $statuses
        ];
    }

    /**
     * Retorna un estado de ficha por su ID.
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function getStatus($id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado obtenido con éxito",
            "data" => $status
        ];
    }

    /**
     * Crea un nuevo estado de ficha.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function createStatus(array $data)
    {
        // Mapea explícitamente los campos para evitar asignación masiva no controlada.
        $status = FichaStatus::create([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            FichaStatus::class,
            $status->id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Estado creado con éxito',
            'data' => $status,
        ];
    }

    /**
     * Actualiza un estado de ficha existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID del estado.
     * @return array
     */
    public function updateStatus(array $data, $id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $statusData = [];

        if (array_key_exists('name', $data)) {
            $statusData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $statusData['description'] = $data['description'];
        }

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($statusData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $status->update($statusData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            FichaStatus::class,
            $status->id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado actualizado con éxito",
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            "data" => $status->fresh(),
        ];
    }

    /**
     * Elimina un estado de ficha por su ID.
     *
     * Verifica que no haya fichas usando este estado antes de eliminar,
     * protegiendo la integridad referencial. Retorna 400 si está en uso.
     * El evento se dispara con el $id original ya que el modelo fue eliminado.
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function deleteStatus($id)
    {
        $status = FichaStatus::find($id);

        if (!$status) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este estado no existe",
            ];
        }

        // exists() es más eficiente que count() porque detiene la consulta al encontrar
        // la primera ficha relacionada, sin traer ni contar todos los resultados.
        if ($status->fichas()->exists()) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar el estado porque tiene fichas asociadas",
            ];
        }

        $status->delete();

        // Se pasa $id y no $status->id porque el modelo ya no existe en BD tras el delete().
        event(new ResourceChanged(
            'eliminar',
            FichaStatus::class,
            $id,
            Auth::id(),
            'Estado de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Estado eliminado con éxito",
        ];
    }
}