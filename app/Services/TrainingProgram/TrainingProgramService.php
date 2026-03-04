<?php

namespace App\Services\TrainingProgram;

use App\Events\ResourceChanged;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de programas de formación.
 *
 * Un programa de formación agrupa fichas bajo un área, nivel de cualificación
 * y coordinador responsable. Aplica restricciones de visibilidad por rol en
 * los métodos de consulta:
 * - COORDINADOR: solo ve los programas donde es coordinador asignado.
 * - ADMIN u otros: ve todos los programas sin restricción.
 *
 * Incluye cálculo derivado de trimesters_lective en getById():
 * trimestres lectivos = max(0, (duración_meses - 6) / 3),
 * descontando los 6 meses de etapa productiva del total de la carrera.
 */
class TrainingProgramService
{
    /**
     * Retorna una lista paginada de programas de formación con filtros opcionales.
     *
     * Aplica restricción de visibilidad para COORDINADOR. Soporta filtros por
     * nombre del programa, área y nivel de cualificación.
     *
     * @param  int  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        $userId   = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Query base con select explícito + relaciones necesarias para el listado.
        $query = TrainingProgram::select([
            'id',
            'name',
            'duration',
            'area_id',
            'qualification_level_id',
            'coordinator_id',
        ])
            ->with([
                'area:id,name',
                'qualificationLevel:id,name',
                // Solo id del coordinador + profile: evita cargar campos sensibles del User.
                'coordinator:id',
                'coordinator.profile:user_id,first_name,last_name',
            ])
            // withCount('fichas'): cuenta fichas sin cargarlas en memoria.
            ->withCount('fichas');

        // COORDINADOR: solo ve los programas donde está asignado como coordinador.
        if ($roleCode === 'COORDINADOR') {
            $query->where('coordinator_id', $userId);
        }
        // ADMIN sin restricción de alcance.

        // Filtros opcionales del request.
        if (request()->filled('program_name')) {
            $query->where('name', 'like', '%' . request('program_name') . '%');
        }

        if (request()->filled('area_name')) {
            $query->whereHas('area', function ($q) {
                $q->where('name', 'like', '%' . request('area_name') . '%');
            });
        }

        if (request()->filled('qualification_level_name')) {
            $query->whereHas('qualificationLevel', function ($q) {
                $q->where('name', 'like', '%' . request('qualification_level_name') . '%');
            });
        }

        $programs = $query->paginate($perPage);

        // Transforma a array plano con campos derivados (coordinator_name, duration formateada).
        $items = $programs->getCollection()->map(function ($program) {
            return [
                'id'                       => $program->id,
                'name'                     => $program->name,
                'area_name'                => $program->area?->name ?? 'Sin área',
                'qualification_level_name' => $program->qualificationLevel?->name ?? 'Sin titulación',
                'coordinator_name'         => $program->coordinator?->profile
                    ? trim($program->coordinator->profile->first_name . ' ' . $program->coordinator->profile->last_name)
                    : 'Sin coordinador',
                'fichas_count' => (int) ($program->fichas_count ?? 0),
                // Concatena unidad 'meses' para que el frontend no necesite formatear.
                'duration' => ($program->duration ?? 0) . ' meses',
            ];
        });

        // Bloque de paginación reutilizado en ambas ramas.
        $paginate = [
            'current_page' => $programs->currentPage(),
            'per_page'     => $programs->perPage(),
            'total'        => $programs->total(),
            'last_page'    => $programs->lastPage(),
            'from'         => $programs->firstItem(),
            'to'           => $programs->lastItem(),
        ];

        if ($items->isEmpty()) {
            return [
                'error'    => false,
                'code'     => 200,
                'message'  => 'No hay programas de formación registrados',
                'data'     => [],
                'paginate' => $paginate,
            ];
        }

        return [
            'error'    => false,
            'code'     => 200,
            'message'  => 'Programas de formación obtenidos exitosamente',
            'data'     => $items,
            'paginate' => $paginate,
        ];
    }

    /**
     * Retorna todos los programas como lista plana para selects del frontend.
     *
     * Sin paginación: el frontend necesita la lista completa para poblar dropdowns.
     * Aplica restricción de visibilidad para COORDINADOR y GESTOR_FICHAS.
     *
     * @return array
     */
    public function getAllForSelect()
    {
        $userId   = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        $query = TrainingProgram::select('id', 'name')
            ->orderBy('name', 'asc');

        // Restricciones de visibilidad según el rol activo.
        if ($roleCode === 'COORDINADOR') {
            // El coordinador solo ve sus programas asignados.
            $query->where('coordinator_id', $userId);
        } elseif ($roleCode === 'GESTOR') {
            // El gestor solo ve programas que tienen fichas donde él es gestor.
            $query->whereHas('fichas', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        }
        // ADMIN sin restricción de alcance.

        if (request()->filled('program_name')) {
            $query->where('name', 'like', '%' . request('program_name') . '%');
        }

        $programs = $query->get();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Programas de formación obtenidos exitosamente',
            'data'    => $programs,
        ];
    }

    /**
     * Retorna el detalle completo de un programa de formación por su ID.
     *
     * Incluye más campos que getAll() (description, created_at, apprentices_count)
     * y calcula trimesters_lective: trimestres lectivos descontando 6 meses de
     * etapa productiva del total de la carrera.
     * Aplica restricción de visibilidad para COORDINADOR.
     *
     * @param  mixed  $id  ID del programa de formación.
     * @return array
     */
    public function getById($id)
    {
        $userId   = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        $query = TrainingProgram::select([
            'id',
            'name',
            'description',
            'duration',
            'area_id',
            'qualification_level_id',
            'coordinator_id',
            'created_at',
            'updated_at',
        ])
            ->with([
                'area:id,name',
                'qualificationLevel:id,name',
                'coordinator:id',
                'coordinator.profile:user_id,first_name,last_name',
            ])
            // Cuenta fichas y aprendices para las métricas del panel de detalle.
            ->withCount(['fichas', 'apprentices']);

        // COORDINADOR: el whereHas garantiza que no pueda acceder a programas de otro coordinador.
        if ($roleCode === 'COORDINADOR') {
            $query->where('coordinator_id', $userId);
        }

        $program = $query->find($id);

        if (!$program) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Programa de formación no encontrado',
                'data'    => [],
            ];
        }

        $duration = (int) ($program->duration ?? 0);

        // Fórmula: trimestres lectivos = (duración total - 6 meses productiva) / 3 meses por trimestre.
        // max(0, ...) evita valores negativos para programas de duración muy corta.
        $trimestersLective = (int) max(0, ($duration - 6) / 3);

        $item = [
            'id'                       => $program->id,
            'name'                     => $program->name,
            'area_id'                  => $program->area_id,
            'area_name'                => $program->area?->name ?? 'Sin área',
            'qualification_level_id'   => $program->qualification_level_id,
            'qualification_level_name' => $program->qualificationLevel?->name ?? 'Sin titulación',
            'coordinator_id'           => $program->coordinator_id,
            'coordinator_name'         => $program->coordinator?->profile
                ? trim($program->coordinator->profile->first_name . ' ' . $program->coordinator->profile->last_name)
                : 'Sin coordinador',
            'description'        => $program->description,
            'fichas_count'       => (int) ($program->fichas_count ?? 0),
            'apprentices_count'  => (int) ($program->apprentices_count ?? 0),
            'duration'           => $duration,
            // Campo calculado: trimestres con actividad académica (sin etapa productiva).
            'trimesters_lective' => $trimestersLective,
            'created_at'         => $program->created_at?->toDateString(),
            'updated_at'         => $program->updated_at?->toDateString(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Programa de formación obtenido exitosamente',
            'data'    => $item,
        ];
    }

    /**
     * Crea un nuevo programa de formación.
     *
     * coordinator_id y description son opcionales: un programa puede crearse
     * sin coordinador asignado y asignarse después vía update().
     *
     * @param  array  $data  Datos validados (name, duration, qualification_level_id, area_id requeridos).
     * @return array
     */
    public function create(array $data)
    {
        // Extracción explícita de campos para proteger contra mass assignment inesperado.
        $program = TrainingProgram::create([
            'name'                   => $data['name'],
            'description'            => $data['description'] ?? null,
            'duration'               => $data['duration'],
            'qualification_level_id' => $data['qualification_level_id'],
            'area_id'                => $data['area_id'],
            // coordinator_id es nullable: el programa puede crearse sin coordinador asignado.
            'coordinator_id'         => $data['coordinator_id'] ?? null,
        ]);

        event(new ResourceChanged(
            'crear',
            TrainingProgram::class,
            $program->id,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Programa de formación creado exitosamente',
            'data'    => $program,
        ];
    }

    /**
     * Actualiza un programa de formación existente.
     *
     * Si no hay campos válidos en $data, retorna éxito silencioso con el registro
     * sin modificar (mismo comportamiento que RoleService).
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID del programa de formación.
     * @return array
     */
    public function update(array $data, $id)
    {
        $program = TrainingProgram::find($id);

        if (!$program) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Programa de formación no encontrado',
                'data'    => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $programData = [];

        if (array_key_exists('name', $data))                   $programData['name']                   = $data['name'];
        if (array_key_exists('description', $data))            $programData['description']            = $data['description'] ?? null;
        if (array_key_exists('duration', $data))               $programData['duration']               = $data['duration'];
        if (array_key_exists('qualification_level_id', $data)) $programData['qualification_level_id'] = $data['qualification_level_id'];
        if (array_key_exists('area_id', $data))                $programData['area_id']                = $data['area_id'];
        if (array_key_exists('coordinator_id', $data))         $programData['coordinator_id']         = $data['coordinator_id'] ?? null;

        // Sin campos válidos: retorna éxito silencioso con el registro sin modificar.
        if (empty($programData)) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay datos para actualizar',
                'data'    => $program,
            ];
        }

        $program->update($programData);

        event(new ResourceChanged(
            'actualizar',
            TrainingProgram::class,
            $program->id,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Programa de formación actualizado exitosamente',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $program->fresh(),
        ];
    }

    /**
     * Elimina un programa de formación por su ID.
     *
     * Verifica integridad referencial antes de eliminar: no permite borrar
     * programas con fichas asociadas. Usa exists() en lugar de count()
     * para eficiencia (para en el primer resultado encontrado).
     *
     * @param  mixed  $id  ID del programa de formación.
     * @return array
     */
    public function delete($id)
    {
        $program = TrainingProgram::find($id);

        if (!$program) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Programa de formación no encontrado',
                'data'    => [],
            ];
        }

        // exists() es ideal para verificar dependencias sin contar ni cargar modelos [web:54].
        if ($program->fichas()->exists()) {
            return [
                'error'   => true,
                'code'    => 409,
                'message' => 'No se puede eliminar el programa de formación porque tiene fichas asociadas',
                'data'    => [],
            ];
        }

        $deletedId = $program->id;

        $program->delete();

        event(new ResourceChanged(
            'eliminar',
            TrainingProgram::class,
            $deletedId,
            Auth::id(),
            'Programa de formación'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Programa de formación eliminado exitosamente',
            'data'    => [],
        ];
    }
}
