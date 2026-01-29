<?php

namespace App\Services\RealClass;

use App\Events\ResourceChanged;
use App\Listeners\NotifyGestorOnAttendanceOrRealClass;
use App\Models\RealClass;
use App\Models\ScheduleSession;
use App\Services\Attendance\AttendanceService;
use App\Services\NoClassDay\NoClassDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RealClassService
{

    protected $noClassDayService, $attendanceService;

    public function __construct(NoClassDayService $noClassDayService, AttendanceService $attendanceService)
    {
        $this->noClassDayService = $noClassDayService;
        $this->attendanceService = $attendanceService;
    }


    public function getAll(Request $request, $perPage = 10)
    {
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'shift_id',
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
                'shift:id,name,start_time,end_time',

                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                'scheduleSession:id,schedule_id',
                'scheduleSession.schedule:id,ficha_term_id',
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id',
                'scheduleSession.schedule.fichaTerm.term:id,name',

                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name',
            ])
            ->withCount([
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ]);

        if ($request->filled('date')) {
            $query->whereDate('execution_date', $request->date);
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('ficha_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', function ($q) use ($request) {
                $q->where('id', $request->ficha_id);
            });
        }

        if ($request->filled('training_program_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', function ($q) use ($request) {
                $q->where('training_program_id', $request->training_program_id);
            });
        }

        if ($request->filled('term_id')) {
            $query->whereHas('scheduleSession.schedule.fichaTerm', function ($q) use ($request) {
                $q->where('term_id', $request->term_id);
            });
        }

        $query->orderBy('execution_date', 'desc')
            ->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => function ($q) {
                $q->withCount('apprentices');
            }
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

                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio' => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay clases reales registradas",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Clases reales obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $realClasses->currentPage(),
                "per_page" => $realClasses->perPage(),
                "total" => $realClasses->total(),
                "last_page" => $realClasses->lastPage(),
                "from" => $realClasses->firstItem(),
                "to" => $realClasses->lastItem(),
            ],
        ];
    }

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
                'shift_id',
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
                'shift:id,name,start_time,end_time',

                'instructor:id',
                'instructor.profile:id,user_id,first_name,last_name',

                'scheduleSession:id,schedule_id,day_id,shift_id,instructor_id,start_time,end_time',
                'scheduleSession.day:id,name',
                'scheduleSession.shift:id,name',

                'scheduleSession.schedule:id,ficha_term_id',
                'scheduleSession.schedule.fichaTerm:id,ficha_id,term_id',
                'scheduleSession.schedule.fichaTerm.term:id,name',
                'scheduleSession.schedule.fichaTerm.ficha:id,ficha_number,training_program_id,gestor_id',
                'scheduleSession.schedule.fichaTerm.ficha.trainingProgram:id,name',

                'scheduleSession.instructor:id',
                'scheduleSession.instructor.profile:id,user_id,first_name,last_name',
            ])
            ->withCount([
                'attendances as attendances_count' => function ($q) {
                    $q->whereNotIn('attendance_status_id', [2, 3, 6]);
                },
            ]);

        if ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->where('instructor_id', $userId);
        }

        $realClass = $query->find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        $realClass->load([
            'scheduleSession.schedule.fichaTerm.ficha' => function ($q) {
                $q->withCount('apprentices');
            }
        ]);

        $profile = $realClass->instructor?->profile;
        $instructorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : 'Sin instructor';

        $fichaTerm = $realClass->scheduleSession?->schedule?->fichaTerm;
        $ficha = $fichaTerm?->ficha;

        $attendancesCount = (int) ($realClass->attendances_count ?? 0);
        $apprenticesCount = (int) ($ficha?->apprentices_count ?? 0);

        $ss = $realClass->scheduleSession;
        $dayName = $ss?->day?->name ?? 'Sin día';
        $shiftName = $ss?->shift?->name ?? 'Sin jornada';

        $ssStart = $ss?->start_time ? substr($ss->start_time, 0, 5) : '--:--';
        $ssEnd   = $ss?->end_time ? substr($ss->end_time, 0, 5) : '--:--';

        $ssFirst = $ss?->instructor?->profile?->first_name ?? '';
        $ssLast  = $ss?->instructor?->profile?->last_name ?? '';
        $ssInstructorName = trim("$ssFirst $ssLast") !== '' ? trim("$ssFirst $ssLast") : 'Sin instructor';

        $scheduleSessionLabel = "{$dayName} - {$shiftName} - {$ssStart} - {$ssEnd} - {$ssInstructorName}";

        $classTypeId = (int) ($realClass->class_type_id ?? 0);

        $data = [
            'id' => $realClass->id,

            'class_date' => $realClass->created_at?->toDateString(),
            'updated_at' => $realClass->updated_at?->toDateString(),

            'start_hour' => $realClass->start_hour ? substr($realClass->start_hour, 0, 5) : null,
            'end_hour'   => $realClass->end_hour ? substr($realClass->end_hour, 0, 5) : null,
            'schedule_label' => ($realClass->start_hour && $realClass->end_hour)
                ? (substr($realClass->start_hour, 0, 5) . ' - ' . substr($realClass->end_hour, 0, 5))
                : null,

            'shift' => [
                'id' => $realClass->shift_id,
                'name' => $realClass->shift?->name,
            ],
            'classroom' => [
                'id' => $realClass->classroom_id,
                'name' => $realClass->classroom?->name,
            ],

            'ficha' => [
                'id' => $ficha?->id,
                'number' => $ficha?->ficha_number,
            ],
            'training_program' => [
                'id' => $ficha?->training_program_id,
                'name' => $ficha?->trainingProgram?->name ?? 'Sin programa',
            ],
            'term' => [
                'id' => $fichaTerm?->term_id,
                'name' => $fichaTerm?->term?->name ?? 'Sin trimestre',
            ],

            'instructor' => [
                'id' => $realClass->instructor_id,
                'name' => $instructorName,
            ],

            'class_type' => [
                'id' => $realClass->class_type_id,
                'name' => $realClass->classType?->name ?? 'Sin tipo',
            ],

            'observations' => $realClass->observations,

            'original_date' => ($classTypeId === 3)
                ? ($realClass->original_date?->toDateString() ?? $realClass->original_date)
                : null,

            'attendances_count' => $attendancesCount,
            'apprentices_count' => $apprenticesCount,
            'attendance_ratio' => "{$attendancesCount}/{$apprenticesCount}",

            'schedule_session' => [
                'id' => $realClass->schedule_session_id,
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

    public function getMine(Request $request, $perPage = 10)
    {
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'shift_id',
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
                'shift:id,name,start_time,end_time',

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
            ->where('instructor_id', Auth::id());

        if ($request->filled('date')) $query->whereDate('execution_date', $request->date);
        if ($request->filled('ficha_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $request->ficha_id));
        if ($request->filled('training_program_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('training_program_id', $request->training_program_id));
        if ($request->filled('term_id')) $query->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('term_id', $request->term_id));

        $query->orderBy('execution_date', 'desc')->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $items = $realClasses->getCollection()->map(function ($rc) {
            $profile = $rc->instructor?->profile;
            $instructorName = $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) : 'Sin instructor';

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

                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio' => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return ["error" => false, "code" => 200, "message" => "No hay clases reales registradas"];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Clases reales obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $realClasses->currentPage(),
                "per_page" => $realClasses->perPage(),
                "total" => $realClasses->total(),
                "last_page" => $realClasses->lastPage(),
                "from" => $realClasses->firstItem(),
                "to" => $realClasses->lastItem(),
            ],
        ];
    }

    public function getManaged(Request $request, $perPage = 10)
    {
        $query = RealClass::query()
            ->select([
                'id',
                'instructor_id',
                'class_type_id',
                'classroom_id',
                'shift_id',
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
                'shift:id,name,start_time,end_time',

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
            ->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('gestor_id', Auth::id()))
            ->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('is_current', 1));

        if ($request->filled('date')) $query->whereDate('execution_date', $request->date);
        if ($request->filled('instructor_id')) $query->where('instructor_id', $request->instructor_id);
        if ($request->filled('ficha_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $request->ficha_id));
        if ($request->filled('training_program_id')) $query->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('training_program_id', $request->training_program_id));
        if ($request->filled('term_id')) $query->whereHas('scheduleSession.schedule.fichaTerm', fn($q) => $q->where('term_id', $request->term_id));

        $query->orderBy('execution_date', 'desc')->orderBy('start_hour', 'asc');

        $realClasses = $query->paginate($perPage);

        $realClasses->load([
            'scheduleSession.schedule.fichaTerm.ficha' => fn($q) => $q->withCount('apprentices')
        ]);

        $items = $realClasses->getCollection()->map(function ($rc) {
            $profile = $rc->instructor?->profile;
            $instructorName = $profile ? trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) : 'Sin instructor';

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

                'attendances_count' => $attendancesCount,
                'apprentices_count' => $apprenticesCount,
                'attendance_ratio' => "{$attendancesCount}/{$apprenticesCount}",

                'created_at' => $rc->created_at?->toDateString(),
                'updated_at' => $rc->updated_at?->toDateString(),
            ];
        });

        if ($items->isEmpty()) {
            return ["error" => false, "code" => 200, "message" => "No hay clases reales registradas"];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Clases reales obtenidas con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $realClasses->currentPage(),
                "per_page" => $realClasses->perPage(),
                "total" => $realClasses->total(),
                "last_page" => $realClasses->lastPage(),
                "from" => $realClasses->firstItem(),
                "to" => $realClasses->lastItem(),
            ],
        ];
    }

    public function create($data)
    {
        return DB::transaction(function () use ($data) {
            $executionDate = now()->format('Y-m-d');
            $data['execution_date'] = $executionDate;

            $scheduleSession = ScheduleSession::with('schedule.fichaTerm.ficha')
                ->find($data['schedule_session_id']);

            if (!$scheduleSession || !$scheduleSession->schedule || !$scheduleSession->schedule->fichaTerm) {
                return [
                    'error' => true,
                    'code' => 404,
                    'message' => 'No se pudo resolver la ficha desde la sesión de horario.',
                    'data' => [],
                ];
            }

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

            if (!(bool) ($scheduleSession->schedule->fichaTerm->is_current ?? false)) {
                return [
                    'error' => true,
                    'code' => 409,
                    'message' => 'La sesión no pertenece al trimestre actual de la ficha.',
                    'data' => [],
                ];
            }

            $fichaId = $scheduleSession->schedule->fichaTerm->ficha_id;

            $check = $this->noClassDayService->checkByFichaAndDate($fichaId, $executionDate);

            if (!empty($check['data']['is_no_class_day'])) {
                return [
                    'error' => true,
                    'code' => 409,
                    'message' => 'No se puede registrar la clase real: el día está marcado como día sin clase para la ficha.',
                    'data' => $check['data'],
                ];
            }

            $realClass = RealClass::create($data);

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
                'data' => $realClass->fresh(),
            ];
        });
    }

    public function update($data, $id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        $realClassData = [];

        if (array_key_exists('instructor_id', $data)) $realClassData['instructor_id'] = $data['instructor_id'];
        if (array_key_exists('class_type_id', $data)) $realClassData['class_type_id'] = $data['class_type_id'];
        if (array_key_exists('classroom_id', $data)) $realClassData['classroom_id'] = $data['classroom_id'];
        if (array_key_exists('shift_id', $data)) $realClassData['shift_id'] = $data['shift_id'];
        if (array_key_exists('schedule_session_id', $data)) $realClassData['schedule_session_id'] = $data['schedule_session_id'];
        if (array_key_exists('execution_date', $data)) $realClassData['execution_date'] = $data['execution_date'];
        if (array_key_exists('start_hour', $data)) $realClassData['start_hour'] = $data['start_hour'];
        if (array_key_exists('end_hour', $data)) $realClassData['end_hour'] = $data['end_hour'];
        if (array_key_exists('original_date', $data)) $realClassData['original_date'] = $data['original_date'];
        if (array_key_exists('observations', $data)) $realClassData['observations'] = $data['observations'];

        if (empty($realClassData)) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No hay datos para actualizar',
            ];
        }

        $realClass->update($realClassData);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real actualizada correctamente',
        ];
    }

    public function delete($id)
    {
        $realClass = RealClass::find($id);

        if (!$realClass) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Clase real no encontrada',
            ];
        }

        $realClass->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Clase real eliminada correctamente',
        ];
    }
}
