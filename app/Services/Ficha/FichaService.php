<?php

namespace App\Services\Ficha;

use App\Events\ResourceChanged;
use App\Models\Ficha;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de fichas.
 *
 * Una ficha representa un grupo de aprendices vinculado a un programa de formación,
 * con su gestor, jornada y trimestre activo. Aplica filtros de visibilidad por rol
 * en todos los métodos de consulta (INSTRUCTOR, GESTOR_FICHAS, COORDINADOR).
 */
class FichaService
{
    /**
     * Retorna una lista paginada de fichas con filtros opcionales.
     *
     * Aplica restricciones de visibilidad según el rol activo:
     * - INSTRUCTOR: solo fichas con término activo donde tiene sesiones asignadas.
     * - GESTOR_FICHAS: solo fichas donde es gestor.
     * - COORDINADOR: solo fichas de programas donde es coordinador.
     * Sin rol restrictivo (ej: ADMIN): ve todas las fichas.
     *
     * @param  int  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Selecciona solo los campos necesarios para el listado e incluye relaciones.
        // withCount('apprentices') agrega apprentices_count sin cargar los modelos.
        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'shift_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])->with([
            'gestor.profile:id,user_id,first_name,last_name',
            'trainingProgram:id,name,coordinator_id',
            'status:id,name',
            'shift:id,name',
            'currentFichaTerm:id,ficha_id,term_id,is_current',
            'currentFichaTerm.term:id,name',
        ])->withCount('apprentices');

        // Restricciones de visibilidad según el rol activo del usuario.
        if ($roleCode === 'INSTRUCTOR') {
            // Solo fichas con término activo (is_current=1) donde el instructor tiene sesiones.
            $query->whereHas('currentFichaTerm', fn($q) => $q->where('is_current', 1))
                ->whereHas('currentFichaTerm.schedule.scheduleSessions', function ($q) use ($userId) {
                    $q->where('instructor_id', $userId);
                });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->where('gestor_id', $userId);
        } elseif ($roleCode === 'COORDINADOR') {
            $query->whereHas('trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        }

        // Filtros opcionales enviados en el request.
        if (request()->filled('ficha_number')) {
            $query->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
        }

        if (request()->filled('training_program_name')) {
            $query->whereHas('trainingProgram', function ($q) {
                $q->where('name', 'like', '%' . request('training_program_name') . '%');
            });
        }

        if (request()->filled('status_name')) {
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', '%' . request('status_name') . '%');
            });
        }

        if (request()->filled('term_name')) {
            $query->whereHas('currentFichaTerm.term', function ($q) {
                $q->where('name', 'like', '%' . request('term_name') . '%');
            });
        }

        if (request()->filled('shift_id')) {
            $query->where('shift_id', request('shift_id'));
        }

        // Ordena por número de ficha y pagina los resultados.
        $fichas = $query->orderBy('ficha_number', 'asc')->paginate($perPage);

        // Mapea cada ficha a un array plano con los campos necesarios para la respuesta.
        $items = $fichas->getCollection()->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'ficha_number' => $ficha->ficha_number,

                'gestor_id' => $ficha->gestor_id,
                // Construye el nombre completo del gestor desde el perfil; fallback si no tiene.
                'gestor_name' => $ficha->gestor?->profile
                    ? $ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name
                    : 'Sin gestor',

                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',

                'status_id' => $ficha->status_id,
                'status_name' => $ficha->status?->name ?? 'Sin estado',

                'shift_id' => $ficha->shift_id,
                'shift_name' => $ficha->shift?->name ?? 'Sin jornada',

                'current_term_id' => $ficha->currentFichaTerm?->term?->id,
                'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',

                // Castea a int para evitar que llegue como string desde la BD.
                'apprentices_count' => (int) ($ficha->apprentices_count ?? 0),

                'created_at' => $ficha->created_at?->toDateString(),
                'updated_at' => $ficha->updated_at?->toDateString(),
            ];
        })->values();

        // Si no hay resultados, retorna mensaje informativo con paginación vacía.
        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay fichas registradas',
                'data' => [],
                'paginate' => [
                    'current_page' => $fichas->currentPage(),
                    'per_page' => $fichas->perPage(),
                    'total' => $fichas->total(),
                    'last_page' => $fichas->lastPage(),
                    'from' => $fichas->firstItem(),
                    'to' => $fichas->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fichas obtenidas con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $fichas->currentPage(),
                'per_page' => $fichas->perPage(),
                'total' => $fichas->total(),
                'last_page' => $fichas->lastPage(),
                'from' => $fichas->firstItem(),
                'to' => $fichas->lastItem(),
            ],
        ];
    }

    /**
     * Retorna fichas en formato simplificado para usar en selects/dropdowns.
     *
     * Aplica las mismas restricciones de rol que getAll() pero sin paginación,
     * ya que el frontend necesita todas las opciones disponibles a la vez.
     *
     * @return array
     */
    public function select()
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Solo selecciona los campos necesarios para un select: id, número y nombre del programa.
        $query = Ficha::select([
            'id',
            'ficha_number',
            'training_program_id',
            'shift_id',
        ])->with([
            'trainingProgram:id,name',
            'shift:id,name',
        ]);

        // Aplica las mismas restricciones de visibilidad por rol que en getAll().
        if ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('currentFichaTerm', fn($q) => $q->where('is_current', 1))
                ->whereHas('currentFichaTerm.schedule.scheduleSessions', function ($q) use ($userId) {
                    $q->where('instructor_id', $userId);
                });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->where('gestor_id', $userId);
        } elseif ($roleCode === 'COORDINADOR') {
            $query->whereHas('trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        }

        // get() en lugar de paginate() porque el select necesita todas las opciones de una vez.
        $fichas = $query->orderBy('ficha_number', 'asc')->get();

        $items = $fichas->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'ficha_number' => $ficha->ficha_number,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',
                'shift_name' => $ficha->shift?->name ?? 'Sin jornada',
            ];
        })->values();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fichas obtenidas con éxito',
            'data' => $items
        ];
    }

    /**
     * Retorna el detalle completo de una ficha por su ID.
     *
     * Incluye todos los términos de la ficha ordenados por fecha de inicio,
     * además del término activo con su fase. Aplica las mismas restricciones por rol.
     *
     * @param  mixed  $id  ID de la ficha.
     * @return array
     */
    public function getById($id)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Carga campos completos e incluye relaciones para el detalle de la ficha.
        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'shift_id',
            'ficha_number',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
        ])
            ->with([
                'gestor.profile:id,user_id,first_name,last_name',
                'trainingProgram:id,name,coordinator_id',
                'status:id,name',
                'shift:id,name',

                'currentFichaTerm:id,ficha_id,term_id,phase_id,start_date,end_date,is_current',
                'currentFichaTerm.term:id,name',
                'currentFichaTerm.phase:id,name',

                // Carga todos los términos ordenados cronológicamente para mostrar el historial.
                'fichaTerms' => function ($q) {
                    $q->select([
                        'id',
                        'ficha_id',
                        'term_id',
                        'phase_id',
                        'start_date',
                        'end_date',
                        'is_current',
                    ])->orderBy('start_date', 'asc');
                },
                'fichaTerms.term:id,name',
                'fichaTerms.phase:id,name',
                'fichaTerms.schedule:id,ficha_term_id',
            ])
            ->withCount('apprentices');

        // Aplica restricciones de visibilidad según el rol activo.
        if ($roleCode === 'GESTOR_FICHAS') {
            $query->where('gestor_id', $userId);
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('currentFichaTerm', fn($q) => $q->where('is_current', 1))
                ->whereHas('currentFichaTerm.schedule.scheduleSessions', function ($q) use ($userId) {
                    $q->where('instructor_id', $userId);
                });
        } elseif ($roleCode === 'COORDINADOR') {
            $query->whereHas('trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        }

        // Si la ficha no existe o el rol no tiene acceso a ella, retorna 404.
        $ficha = $query->find($id);

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Ficha no encontrada",
            ];
        }

        // Construye el nombre del gestor fuera del map para mayor legibilidad.
        $gestorName = $ficha->gestor?->profile
            ? trim($ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name)
            : 'Sin gestor';

        $data = [
            'id' => $ficha->id,
            'ficha_number' => $ficha->ficha_number,

            'gestor_id' => $ficha->gestor_id,
            'gestor_name' => $gestorName,

            'training_program_id' => $ficha->training_program_id,
            'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',

            'shift_id' => $ficha->shift_id,
            'shift_name' => $ficha->shift?->name ?? 'Sin jornada',

            'apprentices_count' => (int) ($ficha->apprentices_count ?? 0),

            'status_id' => $ficha->status_id,
            'status_name' => $ficha->status?->name ?? 'Sin estado',

            'start_date' => $ficha->start_date?->toDateString(),
            'end_date' => $ficha->end_date?->toDateString(),

            'current_ficha_term_id' => $ficha->currentFichaTerm?->id ?? null,
            'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',
            'current_phase_name' => $ficha->currentFichaTerm?->phase?->name ?? null,

            // Mapea el historial completo de términos de la ficha con sus datos.
            'ficha_terms' => $ficha->fichaTerms->map(function ($ft) {
                return [
                    'id' => $ft->id,
                    'term_id' => $ft->term_id,
                    'term_name' => $ft->term?->name ?? null,
                    'phase_id' => $ft->phase_id,
                    'phase_name' => $ft->phase?->name ?? null,
                    'start_date' => $ft->start_date?->toDateString(),
                    'end_date' => $ft->end_date?->toDateString(),
                    // Castea a bool para que el frontend pueda evaluar directamente.
                    'is_current' => (bool) $ft->is_current,
                ];
            })->values(),

            'created_at' => $ficha->created_at?->toDateString(),
            'updated_at' => $ficha->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha obtenida con éxito",
            "data" => $data
        ];
    }

    /**
     * Retorna las fichas asociadas a un programa de formación específico.
     *
     * No aplica restricciones por rol; filtra directamente por training_program_id.
     * Retorna 404 si el programa no tiene fichas registradas.
     *
     * @param  mixed  $trainingProgramId  ID del programa de formación.
     * @return array
     */
    public function getByTrainingProgram($trainingProgramId)
    {
        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'shift_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])
            ->with([
                'gestor.profile:id,user_id,first_name,last_name',
                'trainingProgram:id,name',
                'status:id,name',
                'shift:id,name',
            ])
            ->where('training_program_id', $trainingProgramId);

        $fichas = $query->paginate(10);

        $items = $fichas->getCollection()->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'number' => $ficha->ficha_number,
                'gestor_id' => $ficha->gestor_id,
                'gestor_name' => $ficha->gestor?->profile
                    ? $ficha->gestor->profile->first_name . ' ' . $ficha->gestor->profile->last_name
                    : 'Sin gestor',
                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',
                'status_id' => $ficha->status_id,
                'status_name' => $ficha->status?->name ?? 'Sin estado',
                'shift_id' => $ficha->shift_id,
                'shift_name' => $ficha->shift?->name ?? 'Sin jornada',
                'created_at' => $ficha->created_at?->toDateString(),
                'updated_at' => $ficha->updated_at?->toDateString(),
            ];
        });

        // A diferencia de getAll(), aquí un resultado vacío sí es un 404
        // porque se esperan fichas para un programa específico que debería existir.
        if ($items->isEmpty()) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "No hay fichas para este programa",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Fichas del programa obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $fichas->currentPage(),
                "per_page" => $fichas->perPage(),
                "total" => $fichas->total(),
                "last_page" => $fichas->lastPage(),
                "from" => $fichas->firstItem(),
                "to" => $fichas->lastItem(),
            ],
        ];
    }

    /**
     * Retorna las fichas disponibles para crear una clase real.
     *
     * Solo incluye fichas con término activo (is_current=1) que pertenezcan
     * al gestor autenticado o donde el usuario sea instructor con sesiones asignadas.
     * No aplica filtro por rol del request; usa el ID del usuario directamente.
     *
     * @return array
     */
    public function availableForRealClass()
    {
        $userId = Auth::id();

        $query = Ficha::select([
            'id',
            'gestor_id',
            'training_program_id',
            'status_id',
            'shift_id',
            'ficha_number',
            'created_at',
            'updated_at',
        ])->with([
            'trainingProgram:id,name',
            'shift:id,name',
            'currentFichaTerm:id,ficha_id,term_id,is_current',
            'currentFichaTerm.term:id,name',
        ])
            // Solo fichas con término activo; sin término activo no tiene sentido crear clase real.
            ->whereHas('currentFichaTerm', fn($q) => $q->where('is_current', 1))
            ->where(function ($q) use ($userId) {
                // Incluye fichas donde el usuario es gestor O donde es instructor con sesiones.
                // orWhereHas permite que cualquiera de las dos condiciones sea suficiente.
                $q->where('gestor_id', $userId)
                    ->orWhereHas('currentFichaTerm.schedule.scheduleSessions', function ($qq) use ($userId) {
                        $qq->where('instructor_id', $userId);
                    });
            });

        $fichas = $query->orderBy('ficha_number', 'asc')->get();

        $items = $fichas->map(function ($ficha) {
            return [
                'id' => $ficha->id,
                'ficha_number' => $ficha->ficha_number,
                'training_program_id' => $ficha->training_program_id,
                'training_program_name' => $ficha->trainingProgram?->name ?? 'Sin programa',
                'shift_id' => $ficha->shift_id,
                'shift_name' => $ficha->shift?->name ?? 'Sin jornada',
                'current_term_id' => $ficha->currentFichaTerm?->term?->id,
                'current_term_name' => $ficha->currentFichaTerm?->term?->name ?? 'Sin trimestre actual',
            ];
        })->values();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Fichas disponibles obtenidas con éxito',
            'data' => $items
        ];
    }

    /**
     * Crea una nueva ficha con estado inicial activo.
     *
     * El status_id se fija en 1 (activo) en la creación; no se permite
     * que el request lo defina para evitar crear fichas en estado inválido.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create(array $data)
    {
        $ficha = Ficha::create([
            'gestor_id' => $data['gestor_id'],
            'ficha_number' => $data['ficha_number'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'training_program_id' => $data['training_program_id'],
            'shift_id' => $data['shift_id'],
            'status_id' => 1, // Estado inicial fijo: activo.
        ]);

        // Dispara el evento después de la creación para auditoría o notificaciones.
        event(new ResourceChanged(
            'crear',
            Ficha::class,
            $ficha->id,
            Auth::id(),
            'Ficha'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Ficha creada con éxito",
            "data" => $ficha
        ];
    }

    /**
     * Actualiza los datos de una ficha existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     * Retorna 400 si no se envió ningún campo válido para actualizar.
     *
     * @param  mixed  $id    ID de la ficha.
     * @param  array  $data  Campos a actualizar.
     * @return array
     */
    public function update($id, array $data)
    {
        $ficha = Ficha::find($id);

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];
        }

        // Construye el array de campos a actualizar solo con los valores enviados.
        $fichaData = [];

        if (array_key_exists('gestor_id', $data)) $fichaData['gestor_id'] = $data['gestor_id'];
        if (array_key_exists('ficha_number', $data)) $fichaData['ficha_number'] = $data['ficha_number'];
        if (array_key_exists('start_date', $data)) $fichaData['start_date'] = $data['start_date'];
        if (array_key_exists('end_date', $data)) $fichaData['end_date'] = $data['end_date'];
        if (array_key_exists('training_program_id', $data)) $fichaData['training_program_id'] = $data['training_program_id'];
        if (array_key_exists('shift_id', $data)) $fichaData['shift_id'] = $data['shift_id'];
        if (array_key_exists('status_id', $data)) $fichaData['status_id'] = $data['status_id'];

        // Si no se envió ningún campo válido, no tiene sentido continuar.
        if (empty($fichaData)) {
            return [
                "error" => true,
                "code" => 400,
                "message" => "No hay datos para actualizar",
            ];
        }

        $ficha->update($fichaData);

        // Dispara el evento después de confirmar la actualización.
        event(new ResourceChanged(
            'actualizar',
            Ficha::class,
            $ficha->id,
            Auth::id(),
            'Ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha actualizada con éxito",
            "data" => $ficha
        ];
    }

    /**
     * Elimina una ficha por su ID.
     *
     * El evento se dispara con el $id original ya que el modelo
     * fue eliminado y no puede referenciarse después del delete().
     *
     * @param  mixed  $id  ID de la ficha.
     * @return array
     */
    public function delete($id)
    {
        $ficha = Ficha::find($id);

        if (!$ficha) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Esta ficha no existe",
            ];
        }

        $ficha->delete();

        // Se pasa $id y no $ficha->id porque el modelo ya no existe en BD tras el delete().
        event(new ResourceChanged(
            'eliminar',
            Ficha::class,
            $id,
            Auth::id(),
            'Ficha'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Ficha eliminada con éxito",
        ];
    }
}