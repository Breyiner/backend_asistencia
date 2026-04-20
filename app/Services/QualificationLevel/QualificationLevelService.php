<?php

namespace App\Services\QualificationLevel;

use App\Events\ResourceChanged;
use App\Models\QualificationLevel;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de niveles de formación.
 *
 * Los niveles de formación (Técnico, Tecnólogo, Especialización, etc.) son un
 * catálogo global que clasifica los programas de formación. No aplica RBAC ya
 * que son datos de configuración administrados únicamente por ADMIN.
 *
 * Incluye validación de integridad referencial en delete(): no permite eliminar
 * un nivel que tenga programas de formación asociados.
 */
class QualificationLevelService
{
    /**
     * Retorna todos los niveles de formación ordenados alfabéticamente.
     *
     * Sin paginación porque es un catálogo pequeño y estático que el frontend
     * necesita completo para poblar selects de programas de formación.
     *
     * @return array
     */
    public function getAll()
    {
        // orderBy para consistencia visual en selects del frontend.
        // select() limita campos: el frontend solo necesita id + name para los selects.
        $items = QualificationLevel::select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get();

        // isEmpty() es más idiomático en Laravel que count() === 0.
        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay niveles de formación registrados',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Niveles de formación obtenidos con éxito',
            'data' => $items,
        ];
    }

    /**
     * Retorna el detalle de un nivel de formación por su ID.
     *
     * @param  mixed  $id  ID del nivel de formación.
     * @return array
     */
    public function getById($id)
    {
        $item = QualificationLevel::find($id);

        if (!$item) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este nivel de formación no existe',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Nivel de formación obtenido con éxito',
            'data' => $item,
        ];
    }

    /**
     * Crea un nuevo nivel de formación.
     *
     * description es opcional: puede ser null si el nivel no requiere descripción detallada.
     *
     * @param  array  $data  Datos validados (name requerido, description opcional).
     * @return array
     */
    public function create(array $data)
    {
        // Extracción explícita de campos para proteger contra mass assignment inesperado.
        $item = QualificationLevel::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        event(new ResourceChanged(
            'crear',
            QualificationLevel::class,
            $item->id,
            Auth::id(),
            'Nivel de formación'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Nivel de formación creado con éxito',
            'data' => $item,
        ];
    }

    /**
     * Actualiza un nivel de formación existente.
     *
     * Solo actualiza los campos presentes en $data. El evento se dispara
     * únicamente si hay campos que efectivamente cambiar.
     *
     * @param  array  $data  Campos a actualizar (name, description).
     * @param  mixed  $id    ID del nivel de formación.
     * @return array
     */
    public function update(array $data, $id)
    {
        $item = QualificationLevel::find($id);

        if (!$item) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este nivel de formación no existe',
                'data' => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $payload = [];

        if (array_key_exists('name', $data)) $payload['name'] = $data['name'];
        if (array_key_exists('description', $data)) $payload['description'] = $data['description'];

        // Solo ejecuta el update y el evento si hay campos que cambiar.
        if (!empty($payload)) {
            $item->update($payload);

            event(new ResourceChanged(
                'actualizar',
                QualificationLevel::class,
                $item->id,
                Auth::id(),
                'Nivel de formación'
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Nivel de formación actualizado con éxito',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $item->fresh(),
        ];
    }

    /**
     * Elimina un nivel de formación por su ID.
     *
     * Valida integridad referencial antes de eliminar: si existen programas de
     * formación asociados, retorna 422 en lugar de dejar que la BD lance un error
     * de FK constraint (comportamiento más controlado y mensaje legible para el frontend).
     *
     * @param  mixed  $id  ID del nivel de formación.
     * @return array
     */
    public function delete($id)
    {
        // 1) Buscar el nivel de formación por ID.
        $item = QualificationLevel::find($id);

        // 2) Validar existencia para responder 404 controlado.
        if (!$item) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Este nivel de formación no existe',
                'data' => [],
            ];
        }

        // 3) Validar integridad referencial (regla de negocio):
        //    Si este nivel ya está asociado a programas de formación, NO se debe eliminar.
        //    Usamos exists() porque es más eficiente que count(): no cuenta todo,
        //    solo verifica si existe al menos 1 registro relacionado.
        if ($item->trainingPrograms()->exists()) {
            return [
                'error' => true,
                'code' => 409, // Conflicto: no se puede eliminar por dependencias existentes.
                'message' => 'No se puede eliminar este nivel de formación porque tiene programas de formación asociados',
                'data' => [],
            ];
        }

        // 4) Guardar el ID antes de eliminar para auditoría/evento.
        $deletedId = $item->id;

        // 5) Eliminar solo si no hay dependencias.
        $item->delete();

        // 6) Disparar evento con el ID eliminado.
        event(new ResourceChanged(
            'eliminar',
            QualificationLevel::class,
            $deletedId,
            Auth::id(),
            'Nivel de formación'
        ));

        // 7) Respuesta exitosa.
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Nivel de formación eliminado con éxito',
            'data' => [],
        ];
    }
}
