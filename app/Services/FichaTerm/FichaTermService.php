<?php

namespace App\Services\FichaTerm;

use App\Events\ResourceChanged;
use App\Models\FichaTerm;
use App\Models\Schedule;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lógica de negocio para la gestión de trimestres de ficha (FichaTerm).
 *
 * Un FichaTerm representa la asignación de un trimestre académico a una ficha,
 * con su fase, fechas y si es el trimestre activo actualmente.
 * Al crear un trimestre, se genera automáticamente su horario (Schedule) asociado.
 */
class FichaTermService
{
    /**
     * Retorna todos los trimestres de ficha con sus relaciones cargadas.
     *
     * Sin filtros ni paginación; uso interno o administrativo.
     *
     * @return array
     */
    public function getAll()
    {
        // Carga ficha, trimestre y fase para que la respuesta sea legible sin IDs crudos.
        $fichaTerms = FichaTerm::with('ficha', 'term', 'phase')->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestres de la ficha obtenidos correctamente',
            'data' => $fichaTerms
        ];
    }

    /**
     * Retorna un trimestre de ficha por su ID.
     *
     * @param  mixed  $id  ID del FichaTerm.
     * @return array
     */
    public function getById($id)
    {
        $fichaTerm = FichaTerm::with('ficha', 'term', 'phase')->find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestre de la ficha obtenido con éxito',
            'data' => $fichaTerm
        ];
    }

    /**
     * Retorna todos los trimestres de una ficha específica, ordenados cronológicamente.
     *
     * El ordenamiento por start_date permite ver la progresión de trimestres de la ficha.
     *
     * @param  mixed  $fichaId  ID de la ficha.
     * @return array
     */
    public function getByFichaId($fichaId)
    {
        $fichaTerms = FichaTerm::with('ficha', 'term', 'phase')
            ->where('ficha_id', $fichaId)
            ->orderBy('start_date') // Orden cronológico para mostrar el historial de trimestres.
            ->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Trimestres de la ficha obtenidos correctamente',
            'data' => $fichaTerms
        ];
    }

    /**
     * Crea un nuevo trimestre para una ficha y genera su horario automáticamente.
     *
     * Al crear un FichaTerm, se crea también un Schedule vacío asociado, ya que
     * todo trimestre necesita un horario para poder programar clases.
     * Dispara dos eventos: uno por el trimestre y otro por el horario creado.
     *
     * Nota: el catch llama a DB::rollBack() pero no hay un DB::beginTransaction() explícito.
     * Si se necesita atomicidad real entre FichaTerm y Schedule, debería agregarse.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create($data)
    {
        try {
            // Operación 1: Crea el trimestre de la ficha.
            $fichaTerm = FichaTerm::create([
                'term_id' => $data['term_id'],
                'ficha_id' => $data['ficha_id'],
                'phase_id' => $data['phase_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            // Operación 2: Crea el horario vacío vinculado al trimestre recién creado.
            // Se crea automáticamente porque un trimestre sin horario no puede tener clases.
            $schedule = Schedule::create(['ficha_term_id' => $fichaTerm->id]);

            // Dispara un evento por cada recurso creado para auditoría independiente.
            event(new ResourceChanged(
                'crear',
                FichaTerm::class,
                $fichaTerm->id,
                Auth::id(),
                'Trimestre de ficha'
            ));

            event(new ResourceChanged(
                'crear',
                Schedule::class,
                $schedule->id,
                Auth::id(),
                'Horario de trimestre de ficha'
            ));

            return [
                'error' => false,
                'code' => 200,
                'message' => 'Trimestre de Ficha asignado con éxito',
                'data' => $fichaTerm
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                "error" => true,
                "code" => 500,
                "message" => "Ocurrió un error al registrar el trimestre a la ficha  {$e->getMessage()}",
            ];
        }
    }

    /**
     * Actualiza los datos de un trimestre de ficha existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  array  $data  Campos a actualizar.
     * @param  int    $id    ID del FichaTerm.
     * @return array
     */
    public function update($data, int $id)
    {
        $fichaTerm = FichaTerm::find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $fichaTermData = [];

        if (array_key_exists('term_id', $data)) $fichaTermData['term_id'] = $data['term_id'];
        if (array_key_exists('ficha_id', $data)) $fichaTermData['ficha_id'] = $data['ficha_id'];
        if (array_key_exists('phase_id', $data)) $fichaTermData['phase_id'] = $data['phase_id'];
        if (array_key_exists('start_date', $data)) $fichaTermData['start_date'] = $data['start_date'];
        if (array_key_exists('end_date', $data)) $fichaTermData['end_date'] = $data['end_date'];

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($fichaTermData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $fichaTerm->update($fichaTermData);

        event(new ResourceChanged(
            'actualizar',
            FichaTerm::class,
            $fichaTerm->id,
            Auth::id(),
            'Trimestre de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha actualizado con éxito",
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            "data" => $fichaTerm->fresh()
        ];
    }

    /**
     * Establece un trimestre como el activo (is_current) de su ficha.
     *
     * Usa una transacción para garantizar que el cambio sea atómico:
     * primero desactiva todos los trimestres de la ficha, luego activa solo el indicado.
     * Sin transacción, un fallo entre las dos operaciones dejaría la ficha sin trimestre activo
     * o con dos trimestres marcados como activos simultáneamente.
     *
     * El evento se dispara FUERA de la transacción para asegurar que los datos
     * ya estén confirmados en BD cuando los listeners los lean.
     *
     * @param  int  $fichaTermId  ID del trimestre a marcar como activo.
     * @return array
     */
    public function setCurrent(int $fichaTermId): array
    {
        try {
            DB::transaction(function () use ($fichaTermId) {
                $fichaTerm = FichaTerm::find($fichaTermId);

                // Si no existe, sale silenciosamente; el error se maneja fuera con el find posterior.
                if (!$fichaTerm) {
                    return;
                }

                // Paso 1: Desactiva todos los trimestres de la misma ficha.
                FichaTerm::where('ficha_id', $fichaTerm->ficha_id)
                    ->update(['is_current' => false]);

                // Paso 2: Activa solo el trimestre indicado.
                // Ambos pasos son atómicos: si el paso 2 falla, el paso 1 se revierte.
                $fichaTerm->update(['is_current' => true]);
            });

            // Recarga el modelo desde BD con sus relaciones ya confirmadas tras el commit.
            $fichaTerm = FichaTerm::with(['ficha', 'term'])->find($fichaTermId);

            // Solo dispara el evento si el trimestre efectivamente existe.
            if ($fichaTerm) {
                event(new ResourceChanged(
                    'actualizar',
                    FichaTerm::class,
                    $fichaTermId,
                    Auth::id(),
                    'Trimestre de ficha'
                ));
            }

            return [
                'error' => false,
                'code' => 200,
                'message' => 'Trimestre actual establecido correctamente',
                'data' => $fichaTerm
            ];

        } catch (Exception $e) {
            return [
                'error' => true,
                'code' => 500,
                'message' => 'Error al establecer trimestre actual: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un trimestre de ficha por su ID.
     *
     * El evento se dispara con el $id original ya que el modelo
     * fue eliminado y no puede referenciarse después del delete().
     *
     * @param  mixed  $id  ID del FichaTerm.
     * @return array
     */
    public function delete($id)
    {
        $fichaTerm = FichaTerm::find($id);

        if (!$fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Trimestre de la ficha no encontrado'
            ];
        }

        $fichaTerm->delete();

        // Se pasa $id y no $fichaTerm->id porque el modelo ya no existe en BD tras el delete().
        event(new ResourceChanged(
            'eliminar',
            FichaTerm::class,
            $id,
            Auth::id(),
            'Trimestre de ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Trimestre de la ficha eliminado con éxito",
        ];
    }
}