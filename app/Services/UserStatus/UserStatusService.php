<?php

namespace App\Services\UserStatus;

use App\Events\ResourceChanged;
use App\Models\UserStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de estados de usuario.
 *
 * Los estados de usuario (Activo, Inactivo, Suspendido, etc.) son un catálogo
 * global que define el estado operativo de cada usuario del sistema.
 * No aplica RBAC ya que son datos de configuración administrados únicamente por ADMIN.
 *
 * Incluye dos métodos de actualización: updateStatus() (solo name, description)
 * y partialUpdateStatus() (cualquier campo del modelo).
 */
class UserStatusService
{
    /**
     * Retorna todos los estados de usuario.
     *
     * Sin paginación porque es un catálogo pequeño y estático que el frontend
     * necesita completo para poblar selects de gestión de usuarios.
     *
     * @return array
     */
    public function getAll()
    {
        // orderBy para consistencia de respuesta entre llamadas.
        $statuses = UserStatus::orderBy('id')->get();

        // isEmpty() es más idiomático que count() == 0 en Laravel Collections.
        if ($statuses->isEmpty()) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay estados registrados',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Estados obtenidos con éxito',
            'data'    => $statuses,
        ];
    }

    /**
     * Retorna el detalle de un estado de usuario por su ID.
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function getStatus($id)
    {
        $status = UserStatus::find($id);

        if (!$status) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este estado no existe',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Estado obtenido con éxito',
            'data'    => $status,
        ];
    }

    /**
     * Crea un nuevo estado de usuario.
     *
     * Extracción explícita de campos para proteger contra mass assignment
     * inesperado si el Request cambia en el futuro.
     *
     * @param  array  $data  Datos validados (name requerido, description opcional).
     * @return array
     */
    public function createStatus(array $data)
    {
        // Extracción explícita: protege contra campos no deseados en el modelo.
        $status = UserStatus::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        event(new ResourceChanged(
            'crear',
            UserStatus::class,
            $status->id,
            Auth::id(),
            'Estado de usuario',
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Estado creado con éxito',
            'data'    => $status,
        ];
    }

    /**
     * Actualiza un estado de usuario existente (solo name y description).
     *
     * Usa Arr::only() para filtrar solo los campos permitidos en el modelo,
     * protegiendo contra mass assignment inesperado.
     *
     * @param  array  $data  Campos a actualizar (name, description).
     * @param  mixed  $id    ID del estado.
     * @return array
     */
    public function updateStatus(array $data, $id)
    {
        $status = UserStatus::find($id);

        if (!$status) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este estado no existe',
                'data'    => [],
            ];
        }

        // Arr::only(): filtra solo los campos permitidos en el modelo (name, description).
        $status->update(Arr::only($data, ['name', 'description']));

        event(new ResourceChanged(
            'actualizar',
            UserStatus::class,
            $status->id,
            Auth::id(),
            'Estado de usuario',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Estado actualizado con éxito',
            'data'    => $status->fresh(),
        ];
    }

    /**
     * Actualiza parcialmente un estado de usuario (cualquier campo del modelo).
     *
     * A diferencia de updateStatus(), acepta cualquier campo del modelo
     * en $entryData. Uso: actualizaciones dinámicas donde no se conoce la estructura
     * de antemano (ej: campos calculados en el frontend).
     *
     * @param  array  $entryData  Campos a actualizar (cualquier campo del modelo).
     * @param  mixed  $id         ID del estado.
     * @return array
     */
    public function partialUpdateStatus(array $entryData, $id)
    {
        $status = UserStatus::find($id);

        if (!$status) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este estado no existe',
                'data'    => [],
            ];
        }

        $status->update($entryData);

        event(new ResourceChanged(
            'actualizar',
            UserStatus::class,
            $status->id,
            Auth::id(),
            'Estado de usuario',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Estado actualizado con éxito',
            'data'    => $status->fresh(),
        ];
    }

    /**
     * Elimina un estado de usuario por su ID.
     *
     * Verifica integridad referencial antes de eliminar: no permite borrar
     * estados asignados a usuarios activos. Usa exists() para eficiencia.
     *
     * @param  mixed  $id  ID del estado.
     * @return array
     */
    public function deleteStatus($id)
    {
        // 1) Buscar el estado por ID.
        $status = UserStatus::find($id);

        // 2) Validar existencia.
        if (!$status) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este estado no existe',
                'data'    => [],
            ];
        }

        // 3) Integridad referencial: no eliminar si hay usuarios relacionados.
        //    exists() es más eficiente que count(): solo verifica si existe 1 registro [web:54].
        if ($status->users()->exists()) {
            return [
                'error'   => true,
                'code'    => 409, // Conflicto: el estado está en uso por usuarios.
                'message' => 'No se puede eliminar el estado porque tiene usuarios relacionados',
                'data'    => [],
            ];
        }

        // 4) Guardar ID antes de eliminar para auditoría/evento.
        $deletedId = $status->id;

        // 5) Eliminar.
        $status->delete();

        // 6) Evento.
        event(new ResourceChanged(
            'eliminar',
            UserStatus::class,
            $deletedId,
            Auth::id(),
            'Estado de usuario',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Estado eliminado con éxito',
            'data'    => [],
        ];
    }
}
