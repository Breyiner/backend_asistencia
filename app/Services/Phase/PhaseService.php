<?php

namespace App\Services\Phase;

use App\Events\ResourceChanged;
use App\Models\Phase;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de fases.
 *
 * Las fases representan etapas del proceso formativo SENA (Análisis, Planeación,
 * Ejecución, etc.) y son un catálogo global sin restricciones de visibilidad por rol.
 * Solo administradores pueden modificarlas; todos los roles pueden consultarlas.
 */
class PhaseService
{
    /**
     * Retorna todas las fases ordenadas alfabéticamente.
     *
     * Sin paginación porque es un catálogo pequeño y estático que el frontend
     * necesita completo para poblar selects y filtros de fichas/términos.
     *
     * @return array
     */
    public function getAll()
    {
        // select() limita los campos: el frontend solo necesita id + name para los selects.
        $phases = Phase::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        if ($phases->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay fases registradas',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fases obtenidas con éxito',
            'data' => $phases,
        ];
    }

    /**
     * Retorna el detalle de una fase por su ID.
     *
     * A diferencia de getAll(), no limita los select() para exponer
     * todos los campos disponibles del modelo en la vista de detalle.
     *
     * @param  mixed  $id  ID de la fase.
     * @return array
     */
    public function getById($id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Esta fase no existe',
                'data' => [],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fase obtenida con éxito',
            'data' => $phase,
        ];
    }

    /**
     * Crea una nueva fase.
     *
     * Extrae explícitamente solo 'name' del array validado en lugar de pasar
     * $data directamente, protegiendo contra mass assignment inesperado.
     *
     * @param  array  $data  Datos validados (name requerido).
     * @return array
     */
    public function create(array $data)
    {
        // Extracción explícita: aunque el Request ya valida los campos,
        // evita que futuros campos del Request lleguen al create() sin intención.
        $phase = Phase::create([
            'name' => $data['name'],
        ]);

        event(new ResourceChanged(
            'crear',
            Phase::class,
            $phase->id,
            Auth::id(),
            'Fase'
        ));

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Fase creada con éxito',
            'data' => $phase,
        ];
    }

    /**
     * Actualiza una fase existente.
     *
     * Solo actualiza los campos presentes en $data. El evento se dispara
     * únicamente si hay campos que efectivamente cambiar.
     *
     * @param  array  $data  Campos a actualizar (name, description).
     * @param  mixed  $id    ID de la fase.
     * @return array
     */
    public function update(array $data, $id)
    {
        $phase = Phase::find($id);

        if (!$phase) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Esta fase no existe',
                'data' => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $phaseData = [];

        if (array_key_exists('name', $data)) $phaseData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $phaseData['description'] = $data['description'];

        // Solo ejecuta el update y el evento si hay campos que cambiar.
        if (!empty($phaseData)) {
            $phase->update($phaseData);

            event(new ResourceChanged(
                'actualizar',
                Phase::class,
                $phase->id,
                Auth::id(),
                'Fase'
            ));
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fase actualizada con éxito',
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            'data' => $phase->fresh(),
        ];
    }

    /**
     * Elimina una fase por su ID.
     *
     * ⚠️ Precaución: eliminar una fase en uso puede afectar FichaTerm u otros
     * modelos que referencien phase_id si no hay FK con restricción en la migración.
     *
     * @param  mixed  $id  ID de la fase.
     * @return array
     */
    public function delete($id)
    {
        // 1) Buscar la fase por ID.
        $phase = Phase::find($id);

        // 2) Validar existencia para responder 404 controlado.
        if (!$phase) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Esta fase no existe',
                'data' => [],
            ];
        }

        // 3) Validar integridad referencial (regla de negocio):
        //    Si la fase ya está asociada a uno o más trimestres de ficha (FichaTerm),
        //    no se debe permitir eliminarla.
        //    exists() es más eficiente que count(): no cuenta todo, solo verifica si existe 1 registro.
        if ($phase->fichaTerm()->exists()) {
            return [
                'error' => true,
                'code' => 409, // Conflicto: no se puede eliminar por dependencias existentes.
                'message' => 'No se puede eliminar esta fase porque tiene trimestres de ficha asociados',
                'data' => [],
            ];
        }

        // 4) Guardar el ID antes de eliminar para auditoría/evento.
        $deletedId = $phase->id;

        // 5) Eliminar solo si no hay dependencias.
        $phase->delete();

        // 6) Disparar evento con el ID eliminado.
        event(new ResourceChanged(
            'eliminar',
            Phase::class,
            $deletedId,
            Auth::id(),
            'Fase'
        ));

        // 7) Respuesta exitosa.
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fase eliminada con éxito',
            'data' => [],
        ];
    }
}
