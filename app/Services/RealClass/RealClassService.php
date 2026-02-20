<?php

namespace App\Services\RealClass;

use App\Events\ResourceChanged;
use App\Models\RealClass;
use App\Models\ScheduleSession;
use App\Services\Attendance\AttendanceService;
use App\Services\NoClassDay\NoClassDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lógica de negocio para la gestión de clases reales.
 *
 * Una clase real representa una sesión académica efectivamente ejecutada
 * (o reprogramada) por un instructor en una fecha concreta. Se genera a partir
 * de una ScheduleSession del horario activo de la ficha.
 *
 * Responsabilidades principales:
 * - Listar clases con filtros y restricciones RBAC (getAll, getMine, getManaged).
 * - Crear clases con validaciones de negocio (instructor, trimestre activo, días sin clase).
 * - Delegar la creación de asistencias al AttendanceService tras crear una clase.
 * - Verificar días sin clase vía NoClassDayService antes de crear o actualizar.
 *
 * Inyecta NoClassDayService y AttendanceService por constructor para
 * mantener las responsabilidades separadas entre servicios.
 */
class RealClassService
{
    protected $noClassDayService, $attendanceService;

    public function __construct(NoClassDayService $noClassDayService, AttendanceService $attendanceService)
    {
        $this->noClassDayService = $noClassDayService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * Retorna una lista paginada de clases reales con filtros y restricciones por rol.
     *
     * Aplica restricción de visibilidad solo para COORDINADOR (ve únicamente clases
     * de fichas en sus programas). ADMIN, GESTOR e INSTRUCTOR no tienen restricción aquí;
     * para el inbox del instructor se usa getMine(), para el gestor getManaged().
     *
     * Soporta filtros por fecha, instructor, ficha, programa de formación, trimestre y franja.
     * Carga withCount de asistencias excluyendo estados 2 (excusa), 3 (cancelada) y 6 (especial).
     *
     * @param  Request  $request  Filtros opcionales del request.
     * @param  int      $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll(Request $request, $perPage = 10)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Query base con select explícito + eager loading de todas las relaciones necesarias.
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'time_slot_id',
                'schedule_session_id',
                'execution_date',
                'start_hour',
                'end_hour',
                'original_date',
                'observations',
                'created_at',
                'updated_at',
            ])
            ->with([
                'classType:id,name',
                'classroom:id,name',
                'timeSlot:id,name,code,start_time,end_time',

                // Instructor de la clase real (puede diferir del instructor de la sesión en clases reasignadas).
                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                // Cadena de relaciones para llegar a la ficha y programa de formación.
                'scheduleSession:id,schedule_id',
                'scheduleSession.schedule:id,ficha_term_id',
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id',
                'scheduleSession.schedule.fichaTerm.term:id,name',
                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name,coordinator_id',
            ])
            ->withCount([
                // Cuenta asistencias activas: excluye excusas(2), canceladas(3) y especiales(6).
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ]);

        // COORDINADOR: solo ve clases de fichas dentro de sus programas asignados.
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        }
        // ADMIN sin restricción de alcance; GESTOR e INSTRUCTOR usan getManaged()/getMine().

        // Filtros opcionales del request.
        if ($request->filled('date')) $query->whereDate('execution_date', $request->date);
        if ($request->filled('instructor_id')) $query->where('instructor_id', $request->instructor_id);

        if ($request->filled('ficha_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $request->ficha_id));
        }

        if ($request->filled('training_program_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('training_program_id', $request->training_program_id));
        }

        if ($request->filled('term_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('term_id', $request->term_id));
        }

        if ($request->filled('time_slot_id')) {
            $query->where('time_slot_id', $request->time_slot_id);
        }

        // Ordenado por fecha descendente y luego por hora ascendente (más reciente primero, ordenado en el día).
        $query->orderBy('execution_date', 'desc')
            ->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        // Segunda carga lazy: withCount de aprendices sobre la ficha
        // se hace post-paginate para no interferir con el conteo del paginator.
        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $items = $realClasses->getCollection()->map(function ($rc) {
            $profile = $rc->instructor?->profile;
            $instructorName = $profile
                ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
                : 'Sin instructor';

            $fichaTerm = $rc->scheduleSession?->schedule?->fichaTerm;
            $ficha = $fichaTerm?->ficha;

            $attendancesCount = (int) ($rc->attendances_count ?? 0);
            $apprenticesCount = (int) ($ficha?->apprentices_count ?? 0);

            return [
                'id' => $rc->id,

                'class_date' => $rc->execution_date,
                'start_hour' => $rc->start_hour,
                'end_hour' => $rc->end_hour,

                'ficha_id' => $ficha?->id,
                'ficha_number' => $ficha?->ficha_number,

                'training_program_id' => $ficha?->training_program_id,
                'training_program_name' => $ficha?->trainingProgram?->name ?? 'Sin programa',

                'term_id' => $fichaTerm?->term_id,
                'term_name' => $fichaTerm?->term?->name ?? 'Sin trimestre',

                'instructor_id' => $rc->instructor_id,
                'instructor_name' => $instructorName,

                'time_slot_id' => $rc->time_slot_id,
                'time_slot_name' => $rc->timeSlot?->name ?? null,
                'time_slot_code' => $rc->timeSlot?->code ?? null,

                // Ratio de asistencia: asistentes reales / total aprendices de la ficha.
                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio' => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay clases reales registradas',
                'data' => [],
                'paginate' => [
                    'current_page' => $realClasses->currentPage(),
                    'per_page' => $realClasses->perPage(),
                    'total' => $realClasses->total(),
                    'last_page' => $realClasses->lastPage(),
                    'from' => $realClasses->firstItem(),
                    'to' => $realClasses->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clases reales obtenidas con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $realClasses->currentPage(),
                'per_page' => $realClasses->perPage(),
                'total' => $realClasses->total(),
                'last_page' => $realClasses->lastPage(),
                'from' => $realClasses->firstItem(),
                'to' => $realClasses->lastItem(),
            ],
        ];
    }

    /**
     * Retorna el detalle completo de una clase real por su ID.
     *
     * Aplica RBAC completo (GESTOR, INSTRUCTOR, COORDINADOR, ADMIN).
     * Incluye más relaciones que getAll(): día, franja de la sesión, instructor
     * de la sesión original (puede diferir del instructor real) y label descriptivo
     * de la sesión de horario para mostrar en el formulario de edición.
     *
     * @param  mixed  $id  ID de la clase real.
     * @return array
     */
    public function getById($id)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'time_slot_id',
                'schedule_session_id',
                'execution_date',
                'start_hour',
                'end_hour',
                'original_date',
                'observations',
                'created_at',
                'updated_at',
            ])
            ->with([
                'classType:id,name',
                'classroom:id,name',
                'timeSlot:id,name,code,start_time,end_time',

                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                // Más relaciones que getAll(): incluye día, franja e instructor de la sesión original.
                'scheduleSession:id,schedule_id,day_id,time_slot_id,instructor_id,start_time,end_time',
                'scheduleSession.day:id,name',
                'scheduleSession.timeSlot:id,name,code,start_time,end_time',

                'scheduleSession.schedule:id,ficha_term_id',
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id',
                'scheduleSession.schedule.fichaTerm.term:id,name',
                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id,gestor_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name,coordinator_id',

                // Instructor original de la sesión (para comparar con el instructor real si fue reasignada).
                'scheduleSession.instructor:id',
                'scheduleSession.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->withCount([
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ]);

        // RBAC completo: cada rol ve solo las clases de su alcance.
        if ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('gestor_id', $userId));
        } elseif ($roleCode === 'INSTRUCTOR') {
            // El instructor solo ve sus propias clases.
            $query->where('instructor_id', $userId);
        } elseif ($roleCode === 'COORDINADOR') {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        }
        // ADMIN sin restricción de alcance.

        // find() con query builder aplica los whereHas de RBAC antes de buscar por ID.
        $realClass = $query->find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
                'data' => [],
            ];
        }

        // Carga withCount de aprendices post-find para no interferir con la query principal.
        $realClass->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $profile = $realClass->instructor?->profile;
        $instructorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : 'Sin instructor';

        $fichaTerm = $realClass->scheduleSession?->schedule?->fichaTerm;
        $ficha = $fichaTerm?->ficha;

        $attendancesCount = (int) ($realClass->attendances_count ?? 0);
        $apprenticesCount = (int) ($ficha?->apprentices_count ?? 0);

        // Construye el label descriptivo de la sesión de horario para el campo select del formulario.
        $ss = $realClass->scheduleSession;
        $dayName = $ss?->day?->name ?? 'Sin día';
        $timeSlotName = $ss?->timeSlot?->name ?? 'Sin franja';

        // substr(..., 0, 5): recorta timestamps 'HH:MM:SS' a 'HH:MM' para display.
        $ssStart = $ss?->start_time ? substr($ss->start_time, 0, 5) : '--:--';
        $ssEnd   = $ss?->end_time   ? substr($ss->end_time, 0, 5)   : '--:--';

        $ssFirst = $ss?->instructor?->profile?->first_name ?? '';
        $ssLast  = $ss?->instructor?->profile?->last_name  ?? '';
        $ssInstructorName = trim("$ssFirst $ssLast") !== '' ? trim("$ssFirst $ssLast") : 'Sin instructor';

        // Label completo para mostrar la sesión en el select de edición de la clase real.
        $scheduleSessionLabel = "{$dayName} - {$timeSlotName} - {$ssStart} - {$ssEnd} - {$ssInstructorName}";

        $classTypeId = (int) ($realClass->class_type_id ?? 0);

        $data = [
            'id' => $realClass->id,

            // Nota: class_date usa created_at intencionalmente (fecha de registro, no execution_date).
            'class_date' => $realClass->created_at?->toDateString(),
            'updated_at' => $realClass->updated_at?->toDateString(),

            'start_hour' => $realClass->start_hour ? substr($realClass->start_hour, 0, 5) : null,
            'end_hour'   => $realClass->end_hour   ? substr($realClass->end_hour, 0, 5)   : null,
            // Label visual combinado para mostrar rango horario en la UI.
            'schedule_label' => ($realClass->start_hour && $realClass->end_hour)
                ? (substr($realClass->start_hour, 0, 5) . ' - ' . substr($realClass->end_hour, 0, 5))
                : null,

            // Objeto anidado para time_slot (más detalle que getAll que devuelve campos planos).
            'time_slot' => [
                'id'         => $realClass->time_slot_id,
                'name'       => $realClass->timeSlot?->name,
                'code'       => $realClass->timeSlot?->code,
                'start_time' => $realClass->timeSlot?->start_time ? substr($realClass->timeSlot->start_time, 0, 5) : null,
                'end_time'   => $realClass->timeSlot?->end_time   ? substr($realClass->timeSlot->end_time, 0, 5)   : null,
            ],

            'classroom' => [
                'id'   => $realClass->classroom_id,
                'name' => $realClass->classroom?->name,
            ],

            'ficha' => [
                'id'     => $ficha?->id,
                'number' => $ficha?->ficha_number,
            ],
            'training_program' => [
                'id'   => $ficha?->training_program_id,
                'name' => $ficha?->trainingProgram?->name ?? 'Sin programa',
            ],
            'term' => [
                'id'   => $fichaTerm?->term_id,
                'name' => $fichaTerm?->term?->name ?? 'Sin trimestre',
            ],

            'instructor' => [
                'id'   => $realClass->instructor_id,
                'name' => $instructorName,
            ],

            'class_type' => [
                'id'   => $realClass->class_type_id,
                'name' => $realClass->classType?->name ?? 'Sin tipo',
            ],

            'observations' => $realClass->observations,

            // original_date solo es relevante para clases de tipo 3 (recuperación/reprogramación).
            // Para otros tipos se envía null para no confundir al frontend.
            'original_date' => ($classTypeId === 3)
                ? ($realClass->original_date?->toDateString() ?? $realClass->original_date)
                : null,

            'attendances_count' => $attendancesCount,
            'apprentices_count' => $apprenticesCount,
            'attendance_ratio'  => "{$attendancesCount}/{$apprenticesCount}",

            'schedule_session' => [
                'id'    => $realClass->schedule_session_id,
                'label' => $scheduleSessionLabel,
            ],
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real obtenida correctamente',
            'data' => $data,
        ];
    }

    /**
     * Retorna las clases reales del instructor autenticado (inbox del instructor).
     *
     * Scope fijo: solo clases donde instructor_id = Auth::id(). No aplica RBAC
     * adicional porque el filtro de identidad es suficiente restricción de alcance.
     * Soporta los mismos filtros que getAll() excepto instructor_id (implícito).
     *
     * @param  Request  $request  Filtros opcionales del request.
     * @param  int      $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getMine(Request $request, $perPage = 10)
    {
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'time_slot_id',
                'schedule_session_id',
                'execution_date',
                'start_hour',
                'end_hour',
                'original_date',
                'observations',
                'created_at',
                'updated_at',
            ])
            ->with([
                'classType:id,name',
                'classroom:id,name',
                'timeSlot:id,name,code,start_time,end_time',

                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                'scheduleSession:id,schedule_id',
                'scheduleSession.schedule:id,ficha_term_id',
                // is_current incluido para filtrar por trimestre activo si se necesita en el frontend.
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id,is_current',
                'scheduleSession.schedule.fichaTerm.term:id,name',
                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id,gestor_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name',
            ])
            ->withCount([
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ])
            // Scope de identidad: restringe al instructor autenticado.
            ->where('instructor_id', Auth::id());

        if ($request->filled('date')) $query->whereDate('execution_date', $request->date);
        if ($request->filled('ficha_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $request->ficha_id));
        if ($request->filled('training_program_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('training_program_id', $request->training_program_id));
        if ($request->filled('term_id')) $query->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('term_id', $request->term_id));
        if ($request->filled('time_slot_id')) $query->where('time_slot_id', $request->time_slot_id);

        $query->orderBy('execution_date', 'desc')->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $items = $realClasses->getCollection()->map(function ($rc) {
            $profile = $rc->instructor?->profile;
            $instructorName = $profile
                ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
                : 'Sin instructor';

            $fichaTerm = $rc->scheduleSession?->schedule?->fichaTerm;
            $ficha = $fichaTerm?->ficha;

            $attendancesCount = (int) ($rc->attendances_count ?? 0);
            $apprenticesCount = (int) ($ficha?->apprentices_count ?? 0);

            return [
                'id' => $rc->id,
                'class_date' => $rc->execution_date,
                'start_hour' => $rc->start_hour,
                'end_hour'   => $rc->end_hour,

                'ficha_id'     => $ficha?->id,
                'ficha_number' => $ficha?->ficha_number,

                'training_program_id'   => $ficha?->training_program_id,
                'training_program_name' => $ficha?->trainingProgram?->name ?? 'Sin programa',

                'term_id'   => $fichaTerm?->term_id,
                'term_name' => $fichaTerm?->term?->name ?? 'Sin trimestre',

                'instructor_id'   => $rc->instructor_id,
                'instructor_name' => $instructorName,

                'time_slot_id'   => $rc->time_slot_id,
                'time_slot_name' => $rc->timeSlot?->name ?? null,
                'time_slot_code' => $rc->timeSlot?->code ?? null,

                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio'  => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay clases reales registradas',
                'data' => [],
                'paginate' => [
                    'current_page' => $realClasses->currentPage(),
                    'per_page' => $realClasses->perPage(),
                    'total' => $realClasses->total(),
                    'last_page' => $realClasses->lastPage(),
                    'from' => $realClasses->firstItem(),
                    'to' => $realClasses->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clases reales obtenidas con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $realClasses->currentPage(),
                'per_page' => $realClasses->perPage(),
                'total' => $realClasses->total(),
                'last_page' => $realClasses->lastPage(),
                'from' => $realClasses->firstItem(),
                'to' => $realClasses->lastItem(),
            ],
        ];
    }

    /**
     * Retorna las clases reales de las fichas gestionadas por el gestor autenticado.
     *
     * Scope fijo doble: fichas donde gestor_id = Auth::id() Y con término activo (is_current = 1).
     * El filtro is_current evita que el gestor vea clases de trimestres históricos de sus fichas.
     * Soporta los mismos filtros que getAll() incluyendo instructor_id (el gestor supervisa varios).
     *
     * @param  Request  $request  Filtros opcionales del request.
     * @param  int      $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getManaged(Request $request, $perPage = 10)
    {
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'time_slot_id',
                'schedule_session_id',
                'execution_date',
                'start_hour',
                'end_hour',
                'original_date',
                'observations',
                'created_at',
                'updated_at',
            ])
            ->with([
                'classType:id,name',
                'classroom:id,name',
                'timeSlot:id,name,code,start_time,end_time',

                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                'scheduleSession:id,schedule_id',
                'scheduleSession.schedule:id,ficha_term_id',
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id,is_current',
                'scheduleSession.schedule.fichaTerm.term:id,name',
                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id,gestor_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name',
            ])
            ->withCount([
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ])
            // Scope de identidad: solo fichas donde el usuario autenticado es gestor.
            ->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('gestor_id', Auth::id()))
            // Scope de trimestre: solo clases del término vigente (evita historial de términos anteriores).
            ->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('is_current', 1));

        if ($request->filled('date')) $query->whereDate('execution_date', $request->date);
        if ($request->filled('instructor_id')) $query->where('instructor_id', $request->instructor_id);
        if ($request->filled('ficha_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $request->ficha_id));
        if ($request->filled('training_program_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('training_program_id', $request->training_program_id));
        if ($request->filled('term_id')) $query->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('term_id', $request->term_id));
        if ($request->filled('time_slot_id')) $query->where('time_slot_id', $request->time_slot_id);

        $query->orderBy('execution_date', 'desc')->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $items = $realClasses->getCollection()->map(function ($rc) {
            $profile = $rc->instructor?->profile;
            $instructorName = $profile
                ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
                : 'Sin instructor';

            $fichaTerm = $rc->scheduleSession?->schedule?->fichaTerm;
            $ficha = $fichaTerm?->ficha;

            $attendancesCount = (int) ($rc->attendances_count ?? 0);
            $apprenticesCount = (int) ($ficha?->apprentices_count ?? 0);

            return [
                'id' => $rc->id,
                'class_date' => $rc->execution_date,
                'start_hour' => $rc->start_hour,
                'end_hour'   => $rc->end_hour,

                'ficha_id'     => $ficha?->id,
                'ficha_number' => $ficha?->ficha_number,

                'training_program_id'   => $ficha?->training_program_id,
                'training_program_name' => $ficha?->trainingProgram?->name ?? 'Sin programa',

                'term_id'   => $fichaTerm?->term_id,
                'term_name' => $fichaTerm?->term?->name ?? 'Sin trimestre',

                'instructor_id'   => $rc->instructor_id,
                'instructor_name' => $instructorName,

                'time_slot_id'   => $rc->time_slot_id,
                'time_slot_name' => $rc->timeSlot?->name ?? null,
                'time_slot_code' => $rc->timeSlot?->code ?? null,

                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio'  => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay clases reales registradas',
                'data' => [],
                'paginate' => [
                    'current_page' => $realClasses->currentPage(),
                    'per_page' => $realClasses->perPage(),
                    'total' => $realClasses->total(),
                    'last_page' => $realClasses->lastPage(),
                    'from' => $realClasses->firstItem(),
                    'to' => $realClasses->lastItem(),
                ],
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clases reales obtenidas con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $realClasses->currentPage(),
                'per_page' => $realClasses->perPage(),
                'total' => $realClasses->total(),
                'last_page' => $realClasses->lastPage(),
                'from' => $realClasses->firstItem(),
                'to' => $realClasses->lastItem(),
            ],
        ];
    }

    /**
     * Crea una nueva clase real con validaciones de negocio completas.
     *
     * Validaciones en orden antes de persistir:
     *   1. Resuelve la ScheduleSession y su cadena de relaciones (ficha, término).
     *   2. Verifica que instructor_id coincida con el instructor asignado a la sesión.
     *   3. Verifica que la sesión pertenezca al término activo (is_current = 1).
     *   4. Verifica que la fecha de ejecución no esté marcada como día sin clase.
     *   5. **NUEVA: No existe clase real previa del MISMO INSTRUCTOR en MISMA FRANJA HORARIA ese día**
     *
     * Post-creación delega la generación de registros de asistencia al AttendanceService.
     * Todo el flujo corre dentro de una transacción DB para garantizar atomicidad.
     *
     * @param array $data Datos validados desde el request.
     * @return array
     */
    public function create($data)
    {
        return DB::transaction(function () use ($data) {
            // La fecha de ejecución siempre es la fecha actual del servidor (no la envía el cliente).
            $executionDate = now()->format('Y-m-d');
            $data['execution_date'] = $executionDate;

            // Carga la sesión con sus relaciones para resolver ficha_id y validar el término.
            $scheduleSession = ScheduleSession::with('schedule.fichaTerm.ficha')
                ->find($data['schedule_session_id']);

            // Validación 1: la sesión debe existir y tener la cadena schedule → fichaTerm completa.
            if (!$scheduleSession || !$scheduleSession->schedule || !$scheduleSession->schedule->fichaTerm) {
                return [
                    'error' => true,
                    'code' => 404,
                    'message' => 'No se pudo resolver la ficha desde la sesión de horario.',
                    'data' => [],
                ];
            }

            // Validación 2: el instructor_id del request debe coincidir con el de la sesión de horario.
            // Evita que un instructor registre clases en sesiones de otro instructor.
            if (
                empty($data['instructor_id']) ||
                (int) $data['instructor_id'] !== (int) $scheduleSession->instructor_id
            ) {
                return [
                    'error' => true,
                    'code' => 422,
                    'message' => 'El instructor_id no coincide con el instructor asignado a la sesión de horario.',
                    'data' => [],
                ];
            }

            // Validación 3: solo se pueden registrar clases en el término activo de la ficha.
            if (!(bool) ($scheduleSession->schedule->fichaTerm->is_current ?? false)) {
                return [
                    'error' => true,
                    'code' => 409,
                    'message' => 'La sesión no pertenece al trimestre actual de la ficha.',
                    'data' => [],
                ];
            }

            $fichaId = $scheduleSession->schedule->fichaTerm->ficha_id;

            // Validación 4: delega en NoClassDayService para verificar si la fecha está bloqueada.
            $check = $this->noClassDayService->checkByFichaAndDate($fichaId, $executionDate);

            if (!empty($check['data']['is_no_class_day'])) {
                return [
                    'error' => true,
                    'code' => 409,
                    'message' => 'No se puede registrar la clase real: el día está marcado como día sin clase para la ficha.',
                    'data' => $check['data'],
                ];
            }

            // **Validación 5: No existe clase real del MISMO INSTRUCTOR en MISMA FRANJA HORARIA hoy**
            // Previene solapamientos: mismo instructor no puede tener 2 clases simultáneas
            $existingOverlap = RealClass::where('instructor_id', $data['instructor_id'])
                ->where('execution_date', $executionDate)
                ->whereHas('scheduleSession', function ($q) use ($scheduleSession) {
                    // Misma franja horaria: mismo day_id + time_slot_id
                    $q->where('day_id', $scheduleSession->day_id)
                        ->where('time_slot_id', $scheduleSession->time_slot_id);
                })
                ->first();

            if ($existingOverlap) {
                return [
                    'error' => true,
                    'code' => 409,
                    'message' => 'El instructor ya tiene una clase real registrada en esta franja horaria hoy.',
                    'data' => [
                        'conflicting_class_id' => $existingOverlap->id,
                    ],
                ];
            }

            // Todas las validaciones pasaron: crea la clase real.
            $realClass = RealClass::create($data);

            // Delega la creación masiva de registros de asistencia (uno por aprendiz de la ficha).
            $this->attendanceService->createdByFicha($fichaId, $realClass->id);

            event(new ResourceChanged(
                action: 'created',
                subjectType: RealClass::class,
                subjectId: $realClass->id,
                actorUserId: Auth::id(),
                subjectLabel: 'Clase real',
            ));

            return [
                'error' => false,
                'code' => 201,
                'message' => 'Clase real registrada',
                // fresh() recarga desde BD para incluir valores calculados o defaults de la migración.
                'data' => $realClass->fresh(),
            ];
        });
    }


    /**
     * Actualiza una clase real existente.
     *
     * Valida integridad de la nueva sesión si se cambia schedule_session_id.
     * Re-verifica días sin clase si cambia execution_date o schedule_session_id
     * (ya que la ficha podría cambiar también al cambiar la sesión).
     *
     * A diferencia de create(), no corre en transacción porque no hay operaciones
     * compuestas que requieran rollback (solo un update y un check externo).
     *
     * @param  array  $data  Campos a actualizar.
     * @param  mixed  $id    ID de la clase real.
     * @return array
     */
    public function update($data, $id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
                'data' => [],
            ];
        }

        // Construye el array solo con los campos enviados en el request.
        $realClassData = [];

        if (array_key_exists('instructor_id', $data))        $realClassData['instructor_id']        = $data['instructor_id'];
        if (array_key_exists('class_type_id', $data))        $realClassData['class_type_id']        = $data['class_type_id'];
        if (array_key_exists('classroom_id', $data))         $realClassData['classroom_id']         = $data['classroom_id'];
        if (array_key_exists('time_slot_id', $data))         $realClassData['time_slot_id']         = $data['time_slot_id'];
        if (array_key_exists('schedule_session_id', $data))  $realClassData['schedule_session_id']  = $data['schedule_session_id'];
        if (array_key_exists('execution_date', $data))       $realClassData['execution_date']       = $data['execution_date'];
        if (array_key_exists('start_hour', $data))           $realClassData['start_hour']           = $data['start_hour'];
        if (array_key_exists('end_hour', $data))             $realClassData['end_hour']             = $data['end_hour'];
        if (array_key_exists('original_date', $data))        $realClassData['original_date']        = $data['original_date'];
        if (array_key_exists('observations', $data))         $realClassData['observations']         = $data['observations'];

        if (empty($realClassData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
                'data' => [],
            ];
        }

        // Si se cambia la sesión, verifica que la nueva exista antes de continuar.
        if (array_key_exists('schedule_session_id', $realClassData)) {
            if (!ScheduleSession::whereKey($realClassData['schedule_session_id'])->exists()) {
                return [
                    'error' => true,
                    'code' => 404,
                    'message' => 'La sesión de horario no existe',
                    'data' => [],
                ];
            }
        }

        // Resuelve la sesión efectiva: la nueva si fue enviada, o la original del registro.
        $scheduleSessionId = $realClassData['schedule_session_id'] ?? $realClass->schedule_session_id;

        $scheduleSession = ScheduleSession::with('schedule.fichaTerm')
            ->find($scheduleSessionId);

        if (!$scheduleSession || !$scheduleSession->schedule || !$scheduleSession->schedule->fichaTerm) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'No se pudo resolver la ficha desde la sesión de horario.',
                'data' => [],
            ];
        }

        $fichaId = $scheduleSession->schedule->fichaTerm->ficha_id;

        // Resolución de fecha efectiva: la nueva si fue enviada, o la original del registro.
        $executionDate = $realClassData['execution_date'] ?? $realClass->execution_date;

        // Re-verifica día sin clase con la fecha y ficha efectivas (post-cambio).
        $check = $this->noClassDayService->checkByFichaAndDate($fichaId, $executionDate);

        if (!empty($check['data']['is_no_class_day'])) {
            return [
                'error' => true,
                'code' => 409,
                'message' => 'No se puede actualizar: el día está marcado como día sin clase para la ficha.',
                'data' => $check['data'],
            ];
        }

        $realClass->update($realClassData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real actualizada correctamente',
            'data' => $realClass->fresh(),
        ];
    }

    /**
     * Elimina una clase real por su ID.
     *
     * No dispara ResourceChanged porque la eliminación de una clase real
     * es una operación administrativa de corrección, no de auditoría de flujo.
     * Las asistencias asociadas se eliminan por CASCADE en la migración.
     *
     * @param  mixed  $id  ID de la clase real.
     * @return array
     */
    public function delete($id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
                'data' => [],
            ];
        }

        $realClass->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real eliminada correctamente',
            'data' => [],
        ];
    }
}
