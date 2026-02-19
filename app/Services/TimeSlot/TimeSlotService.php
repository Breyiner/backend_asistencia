<?php

namespace App\Services\TimeSlot;

use App\Events\ResourceChanged;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de franjas horarias.
 *
 * Las franjas horarias (MAÑ, TAR, NOC, etc.) definen los bloques de tiempo
 * en que se dictan las clases: código único, nombre legible y rango de horas.
 * Son un catálogo global usado por ScheduleSession y RealClass.
 *
 * Incluye validación de negocio en update(): start_time debe ser menor que end_time.
 * El campo 'code' se normaliza a mayúsculas en create() y update() para consistencia.
 */
class TimeSlotService
{
    /**
     * Retorna todas las franjas horarias ordenadas por hora de inicio.
     *
     * Sin paginación porque es un catálogo pequeño que el frontend necesita
     * completo para poblar selects de sesiones y filtros de clases reales.
     * Ordenado por start_time para mostrar las franjas en orden cronológico.
     *
     * @return array
     */
    public function getAll()
    {
        // orderBy('start_time'): orden cronológico natural (Mañana → Tarde → Noche).
        $items = TimeSlot::orderBy('start_time', 'asc')->get();

        // isEmpty() es más idiomático que count() === 0 en Laravel Collections.
        if ($items->isEmpty()) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay franjas horarias registradas',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Franjas horarias obtenidas con éxito',
            'data'    => $items,
        ];
    }

    /**
     * Retorna el detalle de una franja horaria por su ID.
     *
     * @param  mixed  $id  ID de la franja horaria.
     * @return array
     */
    public function getById($id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Esta franja horaria no existe',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Franja horaria obtenida con éxito',
            'data'    => $item,
        ];
    }

    /**
     * Crea una nueva franja horaria.
     *
     * El código se normaliza a mayúsculas antes de persistir para garantizar
     * consistencia en búsquedas y comparaciones (ej: 'mañ' → 'MAÑ').
     *
     * @param  array  $data  Datos validados (code, name, start_time, end_time requeridos).
     * @return array
     */
    public function create(array $data)
    {
        // strtoupper(): normaliza el código a mayúsculas independientemente de lo que envíe el cliente.
        $code = strtoupper($data['code']);

        // Extracción explícita de campos para proteger contra mass assignment inesperado.
        $item = TimeSlot::create([
            'code'       => $code,
            'name'       => $data['name'],
            'start_time' => $data['start_time'],
            'end_time'   => $data['end_time'],
        ]);

        event(new ResourceChanged(
            'crear',
            TimeSlot::class,
            $item->id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Franja horaria creada con éxito',
            'data'    => $item,
        ];
    }

    /**
     * Actualiza una franja horaria existente.
     *
     * Incluye validación de negocio: si se envían ambas horas, start_time debe
     * ser menor que end_time. Esta validación se hace en el servicio (no solo en
     * el Request) porque depende de la combinación de ambos valores juntos.
     *
     * @param  array  $data  Campos a actualizar (code, name, start_time, end_time).
     * @param  mixed  $id    ID de la franja horaria.
     * @return array
     */
    public function update(array $data, $id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Esta franja horaria no existe',
                'data'    => [],
            ];
        }

        // Construye el array solo con los campos enviados; normaliza code a mayúsculas.
        $payload = [];

        if (array_key_exists('code', $data))       $payload['code']       = strtoupper($data['code']);
        if (array_key_exists('name', $data))       $payload['name']       = $data['name'];
        if (array_key_exists('start_time', $data)) $payload['start_time'] = $data['start_time'];
        if (array_key_exists('end_time', $data))   $payload['end_time']   = $data['end_time'];

        if (empty($payload)) {
            return [
                'error'   => true,
                'code'    => 400,
                'message' => 'No hay datos para actualizar',
                'data'    => [],
            ];
        }

        // Validación de negocio: solo cuando se envían AMBAS horas en el mismo request.
        // Si se envía solo una, el Request debería validar consistencia con el valor existente.
        if (isset($payload['start_time'], $payload['end_time']) && $payload['start_time'] >= $payload['end_time']) {
            return [
                'error'   => true,
                'code'    => 422,
                'message' => 'La hora inicial debe ser menor que la hora final',
                'data'    => [],
            ];
        }

        $item->update($payload);

        event(new ResourceChanged(
            'actualizar',
            TimeSlot::class,
            $item->id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Franja horaria actualizada con éxito',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $item->fresh(),
        ];
    }

    /**
     * Elimina una franja horaria por su ID.
     *
     * ⚠️ Precaución: eliminar una franja en uso puede afectar ScheduleSessions
     * y RealClasses que referencien time_slot_id si no hay FK con restricción.
     *
     * @param  mixed  $id  ID de la franja horaria.
     * @return array
     */
    public function delete($id)
    {
        $item = TimeSlot::find($id);

        if (!$item) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Esta franja horaria no existe',
                'data'    => [],
            ];
        }

        $item->delete();

        // Nota: se usa $item->id (no $id) para consistencia con el patrón del sistema.
        event(new ResourceChanged(
            'eliminar',
            TimeSlot::class,
            $item->id,
            Auth::id(),
            'Franja horaria'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Franja horaria eliminada con éxito',
            'data'    => [],
        ];
    }
}
