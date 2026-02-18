<?php

namespace App\Services\Classroom;

use App\Events\ResourceChanged;
use App\Models\Classroom;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de ambientes (aulas/salones).
 *
 * Los ambientes son catálogos relativamente estables que se asignan a clases reales.
 * CRUD sencillo sin transacciones, ya que cada operación afecta una sola tabla.
 */
class ClassroomService
{
    /**
     * Retorna todos los ambientes ordenados alfabéticamente.
     *
     * Sin paginación ni filtros; el catálogo se consume completo.
     *
     * @return array
     */
    public function getAll()
    {
        $classrooms = Classroom::orderBy('name')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambientes obtenidos correctamente',
            'data' => $classrooms
        ];
    }

    /**
     * Retorna un ambiente por su ID.
     *
     * @param  int  $id  ID del ambiente.
     * @return array
     */
    public function getById(int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Ambiente obtenido correctamente',
            'data' => $classroom
        ];
    }

    /**
     * Crea un nuevo ambiente.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create(array $data)
    {
        $classroom = Classroom::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
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
            'message' => 'Ambiente creado correctamente',
            'data' => $classroom,
        ];
    }

    /**
     * Actualiza un ambiente existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  int    $id    ID del ambiente.
     * @return array
     */
    public function update(array $data, int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $classroomData = [];

        if (array_key_exists('name', $data)) {
            $classroomData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $classroomData['description'] = $data['description'];
        }

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($classroomData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $classroom->update($classroomData);

        // Dispara el evento después de confirmar la actualización.
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
            'message' => 'Ambiente actualizado correctamente',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $classroom->fresh(),
        ];
    }

    /**
     * Elimina un ambiente por su ID.
     *
     * El evento se dispara con el $id original ya que el modelo
     * fue eliminado y no puede referenciarse después del delete().
     *
     * @param  int  $id  ID del ambiente.
     * @return array
     */
    public function delete(int $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Ambiente no encontrado',
            ];
        }

        $classroom->delete();

        // Se pasa $id y no $classroom->id porque el modelo ya no existe en BD tras el delete().
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
            'message' => 'Ambiente eliminado correctamente',
        ];
    }
}