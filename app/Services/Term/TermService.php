<?php

namespace App\Services\Term;

use App\Events\ResourceChanged;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de trimestres.
 *
 * Los trimestres (Trimestre 1, Trimestre 2, etc.) son un catálogo global
 * que estructura los períodos académicos de las fichas. No aplica RBAC
 * ya que son datos de configuración administrados únicamente por ADMIN.
 */
class TermService
{
    /**
     * Retorna todos los trimestres ordenados alfabéticamente.
     *
     * Sin paginación porque es un catálogo pequeño y estático que el frontend
     * necesita completo para poblar selects de FichaTerm y filtros de reportes.
     *
     * @return array
     */
    public function getAll()
    {
        // orderBy para consistencia visual en selects del frontend.
        $terms = Term::orderBy('name')->get();

        // isEmpty() es más idiomático en Laravel que count() == 0.
        if ($terms->isEmpty()) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay trimestres registrados',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Trimestres obtenidos con éxito',
            'data'    => $terms,
        ];
    }

    /**
     * Retorna el detalle de un trimestre por su ID.
     *
     * @param  mixed  $id  ID del trimestre.
     * @return array
     */
    public function getById($id)
    {
        $term = Term::find($id);

        if (!$term) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este trimestre no existe',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Trimestre obtenido con éxito',
            'data'    => $term,
        ];
    }

    /**
     * Crea un nuevo trimestre.
     *
     * Extrae explícitamente solo 'name' del array validado para proteger
     * contra mass assignment inesperado si el Request cambia en el futuro.
     *
     * @param  array  $data  Datos validados (name requerido).
     * @return array
     */
    public function create(array $data)
    {
        $term = Term::create([
            'name' => $data['name'],
        ]);

        event(new ResourceChanged(
            'crear',
            Term::class,
            $term->id,
            Auth::id(),
            'Trimestre',
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Trimestre creado con éxito',
            // Devuelve el modelo creado para que el frontend obtenga el ID sin segunda petición.
            'data'    => $term,
        ];
    }

    /**
     * Actualiza un trimestre existente.
     *
     * Solo actualiza los campos presentes en $data. El evento se dispara
     * únicamente si hay campos que efectivamente cambiar.
     *
     * @param  array  $data  Campos a actualizar (name).
     * @param  mixed  $id    ID del trimestre.
     * @return array
     */
    public function update(array $data, $id)
    {
        $term = Term::find($id);

        if (!$term) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este trimestre no existe',
                'data'    => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $termData = [];

        if (array_key_exists('name', $data)) $termData['name'] = $data['name'];

        // Solo ejecuta el update y el evento si hay campos que cambiar.
        if (!empty($termData)) {
            $term->update($termData);

            event(new ResourceChanged(
                'actualizar',
                Term::class,
                $term->id,
                Auth::id(),
                'Trimestre',
            ));
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Trimestre actualizado con éxito',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $term->fresh(),
        ];
    }

    /**
     * Elimina un trimestre por su ID.
     *
     * ⚠️ Precaución: eliminar un trimestre en uso puede afectar FichaTerms que
     * referencien term_id si no hay FK con restricción en la migración.
     *
     * @param  mixed  $id  ID del trimestre.
     * @return array
     */
    public function delete($id)
    {
        // 1) Buscar el trimestre por ID.
        $term = Term::find($id);

        // 2) Validar existencia.
        if (!$term) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este trimestre no existe',
                'data'    => [],
            ];
        }

        // 3) Integridad referencial: no eliminar si hay ficha_term asociados.
        //    exists() es más eficiente que count().
        if ($term->fichaTerm()->exists()) {
            return [
                'error'   => true,
                'code'    => 409,
                'message' => 'No se puede eliminar este trimestre porque tiene trimestres de ficha (ficha_term) asociados',
                'data'    => [],
            ];
        }

        // 4) Guardar ID antes de borrar para auditoría.
        $deletedId = $term->id;

        // 5) Eliminar.
        $term->delete();

        // 6) Evento.
        event(new ResourceChanged(
            'eliminar',
            Term::class,
            $deletedId,
            Auth::id(),
            'Trimestre',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Trimestre eliminado con éxito',
            'data'    => [],
        ];
    }
}
