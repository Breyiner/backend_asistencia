<?php

namespace App\Services\NoClassReason;

use App\Events\ResourceChanged;
use App\Models\NoClassReason;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de motivos de días sin clase.
 *
 * Los motivos son un catálogo global (festivos, paros, actividades institucionales).
 * NO aplica RBAC por rol ya que son datos compartidos por todas las fichas/programas.
 * Soporta paginación ligera y ordenamiento alfabético.
 */
class NoClassReasonService
{
    /**
     * Retorna una lista paginada de todos los motivos de días sin clase.
     *
     * Incluye eager loading de relaciones mínimas. Soporta filtro opcional por nombre.
     * Ordena alfabéticamente para UX consistente en selects del frontend.
     *
     * @param  int  $perPage  Registros por página (default: 15 para catálogos).
     * @return array
     */
    public function getAll($perPage = 15)
    {
        // Query base optimizada: solo campos necesarios + eager loading (evita N+1).
        $query = NoClassReason::select([
            'id',
            'name',
            'description',
            'created_at',
            'updated_at',
        ])->orderBy('name', 'asc');

        // Filtro por nombre (búsqueda parcial para autocompletado).
        if (request()->filled('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        $reasons = $query->paginate($perPage);

        // Transforma a array plano para respuesta API consistente.
        $items = $reasons->getCollection()->map(function ($reason) {
            return [
                'id' => $reason->id,
                'name' => $reason->name,
                'description' => $reason->description ?? '',
                'created_at' => $reason->created_at?->toDateString(),
                'updated_at' => $reason->updated_at?->toDateString(),
            ];
        })->values();

        // Respuesta con paginación completa (igual que NoClassDayService).
        return [
            'error' => false,
            'code' => 200,
            'message' => $items->isEmpty() 
                ? 'No hay motivos de días sin clase registrados' 
                : 'Motivos de días sin clase obtenidos correctamente',
            'data' => $items,
            'paginate' => [
                'current_page' => $reasons->currentPage(),
                'per_page' => $reasons->perPage(),
                'total' => $reasons->total(),
                'last_page' => $reasons->lastPage(),
                'from' => $reasons->firstItem(),
                'to' => $reasons->lastItem(),
            ],
        ];
    }

    /**
     * Retorna el detalle completo de un motivo por su ID.
     *
     * @param  mixed  $reasonId  ID del motivo.
     * @return array
     */
    public function getById($reasonId)
    {
        // Encuentra con todos los campos (catálogo simple, no necesita whereHas RBAC).
        $reason = NoClassReason::select([
            'id',
            'name',
            'description',
            'created_at',
            'updated_at',
        ])->find($reasonId);

        if (!$reason) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        $data = [
            'id' => $reason->id,
            'name' => $reason->name,
            'description' => $reason->description ?? '',
            'created_at' => $reason->created_at?->toDateString(),
            'updated_at' => $reason->updated_at?->toDateString(),
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo obtenido correctamente',
            'data' => $data,
        ];
    }

    /**
     * Crea un nuevo motivo de día sin clase.
     *
     * @param  array  $data  Datos validados (name requerido, description opcional).
     * @return array
     */
    public function store($data)
    {
        $created = NoClassReason::create($data);

        // Evento de auditoría/notificación (consistente con tu patrón).
        event(new ResourceChanged(
            'crear',
            NoClassReason::class,
            $created->id,
            Auth::id(),
            'Motivo de día sin clase',
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Motivo creado correctamente',
            'data' => $created,
        ];
    }

    /**
     * Actualiza un motivo existente.
     *
     * Solo actualiza campos presentes en $data. Dispara evento solo si hay cambios.
     *
     * @param  mixed  $reasonId  ID del motivo.
     * @param  array  $data      Campos a actualizar.
     * @return array
     */
    public function update($reasonId, $data)
    {
        $reason = NoClassReason::find($reasonId);

        if (!$reason) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        // Construye array solo con campos enviados (igual que original).
        $dataToUpdate = [];
        if (array_key_exists('name', $data)) $dataToUpdate['name'] = $data['name'];
        if (array_key_exists('description', $data)) $dataToUpdate['description'] = $data['description'];

        if (!empty($dataToUpdate)) {
            $reason->update($dataToUpdate);

            event(new ResourceChanged(
                'actualizar',
                NoClassReason::class,
                $reason->id,
                Auth::id(),
                'Motivo de día sin clase',
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo actualizado correctamente',
            'data' => $reason->fresh(),
        ];
    }

    /**
     * Elimina un motivo por su ID.
     *
     * ⚠️ Recomendación: Antes de delete(), verifica foreign keys en no_class_days.
     *
     * @param  mixed  $reasonId  ID del motivo.
     * @return array
     */
    public function destroy($reasonId)
    {
        $reason = NoClassReason::find($reasonId);

        if (!$reason) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Motivo no encontrado',
                'data' => [],
            ];
        }

        // TODO: Verificar si tiene no_class_days asociados antes de eliminar.
        if ($reason->noClassDays()->count() > 0) {
             return [
                'error' => true,
                'code' => 422,
                'message' => 'Este motivo tiene días sin clase asociados. No se puede eliminar.',
                'data' => [],
            ];
        }

        $reason->delete();

        event(new ResourceChanged(
            'eliminar',
            NoClassReason::class,
            $reason->id,
            Auth::id(),
            'Motivo de día sin clase',
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Motivo eliminado correctamente',
            'data' => [],
        ];
    }
}
