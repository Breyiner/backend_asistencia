<?php

namespace App\Services\ClassType;

use App\Events\ResourceChanged;
use App\Models\ClassType;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de tipos de clase.
 *
 * Los tipos de clase son un catálogo base (ej: presencial, virtual, mixta)
 * que se asigna a las clases reales. CRUD sencillo sin transacciones,
 * ya que cada operación afecta una sola tabla.
 */
class ClassTypeService
{
    /**
     * Retorna todos los tipos de clase sin filtros ni paginación.
     *
     * Es un catálogo pequeño y estable, por lo que se trae completo con all().
     *
     * @return array
     */
    public function getAll()
    {
        $classTypes = ClassType::all();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipos de clase obtenidos correctamente',
            'data' => $classTypes
        ];
    }

    /**
     * Retorna un tipo de clase por su ID.
     *
     * @param  mixed  $id  ID del tipo de clase.
     * @return array
     */
    public function getById($id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase obtenido correctamente',
            'data' => $classType
        ];
    }

    /**
     * Crea un nuevo tipo de clase.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create($data)
    {
        $classType = ClassType::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            ClassType::class,
            $classType->id,
            Auth::id(),
            'Tipo de clase'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Tipo de clase creado correctamente',
            'data' => $classType,
        ];
    }

    /**
     * Actualiza un tipo de clase existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID del tipo de clase.
     * @return array
     */
    public function update($data, $id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $classTypeData = [];

        if (array_key_exists('name', $data)) $classTypeData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $classTypeData['description'] = $data['description'];

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($classTypeData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $classType->update($classTypeData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            ClassType::class,
            $classType->id,
            Auth::id(),
            'Tipo de clase'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase actualizado correctamente',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $classType->fresh(),
        ];
    }

    /**
     * Elimina un tipo de clase por su ID.
     *
     * El evento se dispara con el $id original ya que el modelo
     * fue eliminado y no puede referenciarse después del delete().
     *
     * @param  mixed  $id  ID del tipo de clase.
     * @return array
     */
    public function delete($id)
    {
        $classType = ClassType::find($id);

        if (!$classType) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Tipo de clase no encontrado',
            ];
        }

        $classType->delete();

        // Se pasa $id y no $classType->id porque el modelo ya no existe en BD tras el delete().
        event(new ResourceChanged(
            'eliminar',
            ClassType::class,
            $id,
            Auth::id(),
            'Tipo de clase'
        ));

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Tipo de clase eliminado correctamente',
        ];
    }
}