<?php

namespace App\Services\Area;

use App\Events\ResourceChanged;
use App\Models\Area;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de áreas.
 *
 * Centraliza las operaciones CRUD sobre áreas, incluyendo
 * validaciones de integridad referencial antes de eliminar.
 */
class AreaService
{
    /**
     * Retorna una lista paginada de áreas con filtros opcionales.
     *
     * Incluye el conteo de programas de formación asociados a cada área.
     *
     * @param  int  $perPage  Cantidad de registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        // Selecciona los campos necesarios e incluye el conteo de programas relacionados.
        $query = Area::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at'])
            ->withCount(['trainingPrograms']);

        // Filtros opcionales enviados en el request.
        if (request()->filled('area_name')) {
            $query->where('name', 'like', '%' . request('area_name') . '%');
        }

        if (request()->filled('description')) {
            $query->where('description', 'like', '%' . request('description') . '%');
        }

        // Ordena alfabéticamente y pagina los resultados.
        $areas = $query->orderBy('name', 'asc')->paginate($perPage);

        // Mapea cada área a un array plano con los campos necesarios para la respuesta.
        $items = $areas->getCollection()->map(function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                // Castea a int para evitar que llegue como string desde la BD.
                'training_programs_count' => (int) ($area->training_programs_count ?? 0),
                'created_at' => $area->created_at?->toDateString(),
                'updated_at' => $area->updated_at?->toDateString(),
            ];
        });

        // Si no hay resultados, retorna mensaje informativo con paginación vacía.
        if ($items->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay áreas registradas",
                "data" => $items,
                "paginate" => [
                    "current_page" => $areas->currentPage(),
                    "per_page" => $areas->perPage(),
                    "total" => $areas->total(),
                    "last_page" => $areas->lastPage(),
                    "from" => $areas->firstItem(),
                    "to" => $areas->lastItem(),
                ],
            ];
        }

        // Retorna las áreas encontradas junto con la metadata de paginación.
        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $areas->currentPage(),
                "per_page" => $areas->perPage(),
                "total" => $areas->total(),
                "last_page" => $areas->lastPage(),
                "from" => $areas->firstItem(),
                "to" => $areas->lastItem(),
            ],
        ];
    }

    /**
     * Retorna todas las áreas en formato simplificado para usar en selects/dropdowns.
     *
     * No pagina, solo retorna ID y nombre ordenados alfabéticamente.
     *
     * @return array
     */
    public function getAllForSelect()
    {
        // Solo selecciona id y name, que es lo mínimo necesario para un select.
        $query = Area::query()
            ->select(['id', 'name'])
            ->orderBy('name', 'asc');

        // Permite filtrar por nombre en caso de selects con búsqueda.
        if (request()->filled('area_name')) {
            $query->where('name', 'like', '%' . request('area_name') . '%');
        }

        // Usa get() en lugar de paginate() porque el frontend necesita todas las opciones a la vez.
        $areas = $query->get();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas obtenidas con éxito",
            "data" => $areas,
        ];
    }

    /**
     * Retorna el detalle de un área por su ID.
     *
     * Incluye el conteo de programas de formación asociados.
     *
     * @param  mixed  $id  ID del área.
     * @return array
     */
    public function getById($id)
    {
        // Busca el área con sus campos completos y el conteo de programas relacionados.
        $area = Area::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at'])
            ->withCount(['trainingPrograms'])
            ->find($id);

        // Si no existe, retorna 404.
        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        // Construye el array de respuesta con todos los datos del área.
        $item = [
            'id' => $area->id,
            'name' => $area->name,
            'description' => $area->description,
            'training_programs_count' => (int) ($area->training_programs_count ?? 0),
            'created_at' => $area->created_at?->toDateString(),
            'updated_at' => $area->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área obtenida con éxito",
            "data" => $item,
        ];
    }

    /**
     * Crea una nueva área.
     *
     * No requiere transacción porque solo involucra una operación sobre una tabla.
     *
     * @param  array  $data  Datos del área validados desde el request.
     * @return array
     */
    public function create(array $data)
    {
        // Crea el área; description es opcional, se guarda null si no se envía.
        $area = Area::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            Area::class,
            $area->id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Área creada con éxito",
            "data" => $area,
        ];
    }

    /**
     * Actualiza los datos de un área existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID del área.
     * @return array
     */
    public function update(array $data, $id)
    {
        // Verifica que el área exista antes de intentar actualizarla.
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $areaData = [];

        if (array_key_exists('name', $data)) {
            $areaData['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            // Permite limpiar la descripción enviando null explícitamente.
            $areaData['description'] = $data['description'] ?? null;
        }

        // Si no se envió ningún campo válido, retorna sin tocar la BD.
        if (empty($areaData)) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay datos para actualizar",
                "data" => $area,
            ];
        }

        // Ejecuta el update solo con los campos que cambiaron.
        $area->update($areaData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            Area::class,
            $area->id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área actualizada con éxito",
            // fresh() recarga el modelo desde la BD para devolver los datos actualizados.
            "data" => $area->fresh(),
        ];
    }

    /**
     * Elimina un área por su ID.
     *
     * Verifica que no tenga programas de formación asociados antes de eliminar,
     * para proteger la integridad referencial de la base de datos.
     *
     * @param  mixed  $id  ID del área.
     * @return array
     */
    public function delete($id)
    {
        // Verifica que el área exista antes de intentar eliminarla.
        $area = Area::find($id);

        if (!$area) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta área no existe",
            ];
        }

        // exists() es más eficiente que count() porque detiene la consulta al encontrar
        // el primer registro relacionado, sin traer ni contar todos los resultados.
        if ($area->trainingPrograms()->exists()) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No se puede eliminar esta área porque tiene programas de formación asociados",
            ];
        }

        // Solo elimina si no hay programas relacionados.
        $area->delete();

        // Dispara el evento con el ID original ya que el modelo fue eliminado.
        event(new ResourceChanged(
            'eliminar',
            Area::class,
            $id,
            Auth::id(),
            'Área'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Área eliminada con éxito",
        ];
    }
}