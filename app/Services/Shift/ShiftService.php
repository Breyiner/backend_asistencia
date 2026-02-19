<?php

namespace App\Services\Shift;

use App\Events\ResourceChanged;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de jornadas.
 *
 * Las jornadas (Mañana, Tarde, Noche, Madrugada) son un catálogo global
 * que clasifica las fichas según su horario de estudio. No aplica RBAC
 * ya que son datos de configuración administrados únicamente por ADMIN.
 */
class ShiftService
{
    /**
     * Retorna todas las jornadas ordenadas por ID.
     *
     * Sin paginación porque es un catálogo pequeño y estático que el frontend
     * necesita completo para poblar selects de fichas y filtros de reportes.
     * Se ordena por ID (orden de inserción del seeder) en lugar de nombre
     * para respetar el orden lógico: Mañana → Tarde → Noche → Madrugada.
     *
     * @return array
     */
    public function getAll(): array
    {
        $shifts = Shift::orderBy('id')->get();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Jornadas obtenidas correctamente',
            'data'    => $shifts,
        ];
    }

    /**
     * Retorna el detalle de una jornada por su ID.
     *
     * @param  int  $id  ID de la jornada.
     * @return array
     */
    public function getById(int $id): array
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Jornada no encontrada',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Jornada obtenida correctamente',
            'data'    => $shift,
        ];
    }

    /**
     * Crea una nueva jornada.
     *
     * Pasa $data directamente al create(): la validación de campos
     * se delega completamente al StoreShiftRequest.
     *
     * @param  array  $data  Datos validados (name requerido).
     * @return array
     */
    public function create(array $data): array
    {
        $shift = Shift::create($data);

        event(new ResourceChanged(
            'crear',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Jornada creada correctamente',
            'data'    => $shift,
        ];
    }

    /**
     * Actualiza una jornada existente.
     *
     * Solo permite actualizar 'name'. Si no hay campos válidos en $data,
     * retorna error 400 en lugar de ejecutar un UPDATE vacío en BD.
     *
     * @param  array  $data  Campos a actualizar (name).
     * @param  int    $id    ID de la jornada.
     * @return array
     */
    public function update(array $data, int $id): array
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Jornada no encontrada',
                'data'    => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $shiftData = [];

        if (array_key_exists('name', $data)) $shiftData['name'] = $data['name'];

        if (empty($shiftData)) {
            return [
                'error'   => true,
                'code'    => 400,
                'message' => 'No hay datos para actualizar',
                'data'    => [],
            ];
        }

        $shift->update($shiftData);

        event(new ResourceChanged(
            'actualizar',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Jornada actualizada correctamente',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $shift->fresh(),
        ];
    }

    /**
     * Elimina una jornada por su ID.
     *
     * ⚠️ Precaución: eliminar una jornada en uso puede afectar fichas que
     * referencien shift_id si no hay FK con restricción en la migración.
     *
     * @param  int  $id  ID de la jornada.
     * @return array
     */
    public function delete(int $id): array
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Jornada no encontrada',
                'data'    => [],
            ];
        }

        $shift->delete();

        event(new ResourceChanged(
            'eliminar',
            Shift::class,
            $shift->id,
            Auth::id(),
            'Jornada'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Jornada eliminada correctamente',
            'data'    => [],
        ];
    }
}
