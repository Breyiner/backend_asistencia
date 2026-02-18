<?php

namespace App\Services\Classroom;

use App\Events\ResourceChanged;
use App\Models\Classroom;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de ambientes (aulas/salones).
 *
 * Centraliza las operaciones CRUD sobre ambientes, pensado para listados
 * paginados y también para selects en el frontend.
 */
class ClassroomService
{
    /**
     * Retorna una lista paginada de ambientes con filtros opcionales.
     *
     * @param  int  $perPage  Cantidad de registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        // Selecciona los campos necesarios.
        $query = Classroom::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at']);

        // Filtros opcionales enviados en el request.
        if (request()->filled('classroom_name')) {
            $query->where('name', 'like', '%' . request('classroom_name') . '%');
        }

        if (request()->filled('description')) {
            $query->where('description', 'like', '%' . request('description') . '%');
        }

        // Ordena alfabéticamente y pagina los resultados.
        $classrooms = $query->orderBy('name', 'asc')->paginate($perPage);

        // Mapea cada ambiente a un array plano con los campos necesarios.
        $items = $classrooms->getCollection()->map(function ($classroom) {
            return [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'description' => $classroom->description,
                'created_at' => $classroom->created_at?->toDateString(),
                'updated_at' => $classroom->updated_at?->toDateString(),
            ];
        });

        // Si no hay resultados, retorna mensaje informativo con paginación vacía.
        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay ambientes registrados',
                'data' => $items,
                'paginate' => [
                    'current_page' => $classrooms->currentPage(),
                    'per_page' => $classrooms->perPage(),
                    'total' => $classrooms->total(),
                    'last_page' => $classrooms->lastPage(),
                    'from' => $classrooms->firstItem(),
                    'to' => $classrooms->lastItem(),
                ],
            ];
        }

        // Retorna los ambientes encontrados junto con la metadata de paginación.
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambientes obtenidos con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $classrooms->currentPage(),
                'per_page' => $classrooms->perPage(),
                'total' => $classrooms->total(),
                'last_page' => $classrooms->lastPage(),
                'from' => $classrooms->firstItem(),
                'to' => $classrooms->lastItem(),
            ],
        ];
    }

    /**
     * Retorna todos los ambientes en formato simplificado para selects/dropdowns.
     *
     * @return array
     */
    public function getAllForSelect()
    {
        $query = Classroom::query()
            ->select(['id', 'name'])
            ->orderBy('name', 'asc');

        // Filtro opcional por nombre, útil para selects con búsqueda.
        if (request()->filled('classroom_name')) {
            $query->where('name', 'like', '%' . request('classroom_name') . '%');
        }

        $classrooms = $query->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambientes obtenidos con éxito',
            'data' => $classrooms,
        ];
    }

    /**
     * Retorna el detalle de un ambiente por su ID.
     *
     * @param  mixed  $id
     * @return array
     */
    public function getById($id)
    {
        $classroom = Classroom::query()
            ->select(['id', 'name', 'description', 'created_at', 'updated_at'])
            ->find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este ambiente no existe',
            ];
        }

        $item = [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'description' => $classroom->description,
            'created_at' => $classroom->created_at?->toDateString(),
            'updated_at' => $classroom->updated_at?->toDateString(),
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente obtenido con éxito',
            'data' => $item,
        ];
    }

    /**
     * Crea un nuevo ambiente.
     *
     * @param  array  $data
     * @return array
     */
    public function create(array $data)
    {
        $classroom = Classroom::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        event(new ResourceChanged(
            'crear',
            Classroom::class,
            $classroom->id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Ambiente creado con éxito',
            'data' => $classroom,
        ];
    }

    /**
     * Actualiza un ambiente existente.
     *
     * Solo actualiza los campos presentes en $data.
     *
     * @param  array  $data
     * @param  mixed  $id
     * @return array
     */
    public function update(array $data, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este ambiente no existe',
            ];
        }

        $classroomData = [];

        if (array_key_exists('name', $data)) {
            $classroomData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $classroomData['description'] = $data['description'] ?? null;
        }

        // Si no se envió ningún campo válido, no toca la BD.
        if (empty($classroomData)) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay datos para actualizar',
                'data' => $classroom,
            ];
        }

        $classroom->update($classroomData);

        event(new ResourceChanged(
            'actualizar',
            Classroom::class,
            $classroom->id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente actualizado con éxito',
            'data' => $classroom->fresh(),
        ];
    }

    /**
     * Elimina un ambiente por su ID.
     *
     * @param  mixed  $id
     * @return array
     */
    public function delete($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este ambiente no existe',
            ];
        }

        // Si en un futuro el ambiente tiene relaciones críticas (ej. clases),
        // aquí podrías validar integridad referencial antes de eliminar.

        $classroom->delete();

        event(new ResourceChanged(
            'eliminar',
            Classroom::class,
            $id,
            Auth::id(),
            'Ambiente'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente eliminado con éxito',
        ];
    }
}
