<?php

namespace App\Services\Day;

use App\Events\ResourceChanged;
use App\Models\Day;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de días de la semana.
 *
 * Los días son un catálogo base usado para construir horarios de fichas.
 * Se ordenan por day_number para mantener el orden natural de la semana.
 * CRUD sencillo sin transacciones, ya que cada operación afecta una sola tabla.
 */
class DayService
{
    /**
     * Retorna todos los días ordenados por su número de día en la semana.
     *
     * Se ordena por day_number en lugar de name para respetar el orden
     * natural de la semana (lunes=1, martes=2, etc.) independientemente del idioma.
     *
     * @return array
     */
    public function getAll(): array
    {
        $days = Day::orderBy('day_number')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Días obtenidos correctamente',
            'data' => $days
        ];
    }

    /**
     * Retorna un día por su ID.
     *
     * @param  int  $id  ID del día.
     * @return array
     */
    public function getById(int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día obtenido con éxito',
            'data' => $day
        ];
    }

    /**
     * Crea un nuevo día.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create(array $data): array
    {
        $day = Day::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            Day::class,
            $day->id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Día creado correctamente',
            'data' => $day
        ];
    }

    /**
     * Actualiza un día existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  int    $id    ID del día.
     * @return array
     */
    public function update(array $data, int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $dayData = [];

        if (array_key_exists('name', $data)) {
            $dayData['name'] = $data['name'];
        }
        if (array_key_exists('day_number', $data)) {
            $dayData['day_number'] = $data['day_number'];
        }

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($dayData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $day->update($dayData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            Day::class,
            $day->id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día actualizado con éxito',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $day->fresh()
        ];
    }

    /**
     * Elimina un día por su ID.
     *
     * El evento se dispara con el $id original ya que el modelo
     * fue eliminado y no puede referenciarse después del delete().
     *
     * @param  int  $id  ID del día.
     * @return array
     */
    public function delete(int $id): array
    {
        $day = Day::find($id);

        if (!$day) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Día no encontrado'
            ];
        }

        $day->delete();

        // Se pasa $id y no $day->id porque el modelo ya no existe en BD tras el delete().
        event(new ResourceChanged(
            'eliminar',
            Day::class,
            $id,
            Auth::id(),
            'Día'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Día eliminado correctamente'
        ];
    }
}