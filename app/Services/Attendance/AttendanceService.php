<?php

namespace App\Services\Attendance;

use App\Events\ResourceChanged;
use App\Models\Apprentice;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\Ficha;
use App\Models\RealClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de asistencias.
 *
 * Maneja el registro, actualización y consulta de asistencias por clase real,
 * incluyendo el cálculo de horas ausentes y el check-in por escaneo de documento.
 */
class AttendanceService
{
    /**
     * Retorna todas las asistencias con sus relaciones cargadas.
     *
     * Uso interno o administrativo; no filtra ni pagina.
     *
     * @return array
     */
    public function getAll()
    {
        // Carga las relaciones de estado y clase real para cada asistencia.
        $attendances = Attendance::with(['attendanceStatus', 'realClass'])->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencias obtenidas correctamente',
            'data' => $attendances
        ];
    }

    /**
     * Retorna el detalle de una asistencia por su ID.
     *
     * @param  mixed  $id  ID de la asistencia.
     * @return array
     */
    public function getById($id)
    {
        // Busca la asistencia con sus relaciones; retorna 404 si no existe.
        $attendance = Attendance::with(['attendanceStatus', 'realClass'])->find($id);

        if (!$attendance) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Asistencia no encontrada',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencia obtenida correctamente',
            'data' => $attendance
        ];
    }

    /**
     * Retorna todas las asistencias de una clase real específica con su resumen.
     *
     * Incluye el conteo de aprendices por estado (presente, ausente, tardanza, etc.)
     *
     * @param  mixed  $realClassId  ID de la clase real.
     * @return array
     */
    public function byClassRealId($realClassId)
    {
        // Carga asistencias con estado y perfil del aprendiz para construir la respuesta.
        $attendances = Attendance::with([
            'attendanceStatus:id,name,code',
            'apprentice.profile:id,user_id,first_name,last_name',
        ])
            ->where('real_class_id', $realClassId)
            ->get()
            // Ordena por apellido del aprendiz para facilitar la lectura en el frontend.
            ->sortBy(fn($a) => $a->apprentice?->profile?->last_name ?? '')
            ->values()
            ->map(function ($a) {
                // Construye el nombre completo del aprendiz desde el perfil.
                $p = $a->apprentice?->profile;
                $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

                return [
                    'id' => $a->id,
                    'real_class_id' => $a->real_class_id,
                    'apprentice_id' => $a->apprentice_id,
                    'apprentice_full_name' => $fullName,
                    'attendance_status' => [
                        'id' => $a->attendanceStatus?->id,
                        'name' => $a->attendanceStatus?->name,
                        'code' => $a->attendanceStatus?->code,
                    ],
                    'observations' => $a->observations,
                    'entry_hour' => $a->entry_hour,
                    'absent_hours' => $a->absent_hours,
                ];
            });

        if ($attendances->isEmpty()) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'No hay asistencias para esta clase real',
            ];
        }

        // Agrupa por código de estado y cuenta cuántos aprendices hay en cada grupo.
        $countsByCode = $attendances
            ->groupBy(fn($a) => $a['attendance_status']['code'] ?? 'unknown')
            ->map(fn($group) => $group->count())
            ->toArray();

        // Garantiza que todos los estados posibles aparezcan en el resumen aunque tengan 0.
        foreach (['present', 'absent', 'excused_absence', 'late', 'early_exit', 'unregistered'] as $c) {
            if (!array_key_exists($c, $countsByCode)) $countsByCode[$c] = 0;
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencias de clase obtenidas correctamente',
            'data' => $attendances,
            'summary' => [
                'total' => $attendances->count(),
                'counts_by_code' => $countsByCode,
            ],
        ];
    }

    /**
     * Crea un registro de asistencia manual.
     *
     * Calcula las horas ausentes automáticamente según el estado y la hora de entrada.
     *
     * @param  array  $data  Datos de la asistencia validados desde el request.
     * @return array
     */
    public function create($data)
    {
        // Si no se envió hora de entrada, se usa la hora actual del servidor.
        if (!$data['entry_hour']) {
            $data['entry_hour'] = now()->format('H:i');
        }

        // Calcula las horas ausentes antes de persistir, según el estado y la hora de entrada.
        $data['absent_hours'] = $this->calculateAbsentHours($data);

        Attendance::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Asistencia registrada correctamente',
        ];
    }

    /**
     * Elimina un registro de asistencia por su ID.
     *
     * @param  mixed  $id  ID de la asistencia.
     * @return array
     */
    public function delete($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Asistencia no encontrada',
            ];
        }

        $attendance->delete();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencia eliminada correctamente',
        ];
    }

    /**
     * Crea los registros de asistencia iniciales para todos los aprendices activos de una ficha.
     *
     * Se llama al crear una clase real, dejando a todos los aprendices en estado "sin registrar".
     * No retorna respuesta porque se invoca como proceso secundario desde otro servicio.
     *
     * @param  mixed  $fichaId      ID de la ficha.
     * @param  mixed  $realClassId  ID de la clase real recién creada.
     * @return void
     */
    public function createdByFicha($fichaId, $realClassId)
    {
        // Trae solo los aprendices activos (status_id = 1) de la ficha.
        $apprentices = Apprentice::where('ficha_id', $fichaId)
            ->where('status_id', 1)
            ->get();

        // Obtiene el ID del estado "sin registrar" desde la BD por su código.
        $unregisteredStatusId = (int) AttendanceStatus::where('code', 'unregistered')->value('id');

        // Si el estado no existe en BD, aborta silenciosamente (el seeder no fue ejecutado).
        if (!$unregisteredStatusId) {
            return;
        }

        // Crea un registro de asistencia por cada aprendiz con estado "sin registrar".
        foreach ($apprentices as $apprentice) {
            Attendance::create([
                'real_class_id' => $realClassId,
                'apprentice_id' => $apprentice->id,
                'attendance_status_id' => $unregisteredStatusId
            ]);
        }
    }

    /**
     * Actualiza el estado de asistencia de un aprendiz en una clase real.
     *
     * Aplica reglas de negocio según el estado seleccionado:
     * - absent / unregistered: limpia la hora de entrada.
     * - late: requiere hora de entrada válida (≥16 min después del inicio).
     * - early_exit: requiere observaciones y una hora de entrada previa.
     * - present y otros: registra la hora actual como hora de entrada.
     *
     * @param  array  $data  Datos del update validados desde el request.
     * @param  mixed  $id    ID de la asistencia a actualizar.
     * @return array
     */
    public function update($data, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return ['error' => true, 'code' => 404, 'message' => 'Asistencia no encontrada'];
        }

        if (!array_key_exists('attendance_status_id', $data)) {
            return ['error' => true, 'code' => 422, 'message' => 'attendance_status_id es requerido'];
        }

        // Obtiene los IDs de todos los estados base en una sola consulta.
        $statuses = $this->getStatusIds();
        $absentStatusId       = (int) ($statuses['absent'] ?? 0);
        $lateStatusId         = (int) ($statuses['late'] ?? 0);
        $earlyExitStatusId    = (int) ($statuses['early_exit'] ?? 0);
        $unregisteredStatusId = (int) ($statuses['unregistered'] ?? 0);

        // Si algún estado base no existe, los cálculos serían incorrectos; se aborta.
        if (!$absentStatusId || !$lateStatusId || !$earlyExitStatusId || !$unregisteredStatusId) {
            return [
                'error' => true,
                'code' => 500,
                'message' => 'Faltan estados base en attendance_statuses (codes: absent/late/early_exit/unregistered). Ejecuta el seeder.'
            ];
        }

        // Carga la clase real con su sesión de horario para obtener la hora de inicio y duración.
        $realClass = RealClass::with('scheduleSession')->findOrFail($attendance->real_class_id);

        $statusId = (int) $data['attendance_status_id'];

        // Calcula desde qué hora se considera tardanza (16 minutos después del inicio).
        $start = Carbon::parse($realClass->start_hour);
        $lateFrom = $start->copy()->addMinutes(16);

        $attendanceData = [
            'attendance_status_id' => $statusId,
        ];

        // --- Caso: Salida anticipada ---
        // Requiere observaciones y que ya exista una hora de entrada registrada.
        if ($statusId === $earlyExitStatusId) {
            if (empty($data['observations'])) {
                return ['error' => true, 'code' => 422, 'message' => 'Las observaciones son obligatorias para salida anticipada'];
            }

            if (is_null($attendance->entry_hour)) {
                return ['error' => true, 'code' => 422, 'message' => 'No se puede marcar salida anticipada sin una hora de entrada previa'];
            }

            $attendanceData['observations'] = $data['observations'];

            // Calcula horas ausentes usando la hora de entrada ya existente en el registro.
            $attendanceData['absent_hours'] = $this->calculateAbsentHours([
                'real_class_id' => $attendance->real_class_id,
                'attendance_status_id' => $statusId,
                'entry_hour' => $attendance->entry_hour,
            ]);

            $attendance->update($attendanceData);

            // Recarga el modelo desde BD para devolver los valores ya persistidos.
            $updated = $attendance->fresh();
            $summary = $this->summaryByRealClassId((int) $updated->real_class_id);

            return [
                'error' => false,
                'code' => 200,
                'message' => 'Salida anticipada registrada correctamente',
                'data' => [
                    'id' => $updated->id,
                    'computed' => [
                        'entry_hour' => $updated->entry_hour,
                        'absent_hours' => $updated->absent_hours,
                    ],
                ],
                'summary' => $summary,
            ];
        }

        // --- Caso: Ausente o Sin registrar ---
        // No tiene sentido guardar hora de entrada; se limpia explícitamente.
        if ($statusId === $absentStatusId || $statusId === $unregisteredStatusId) {
            $attendanceData['entry_hour'] = null;

        // --- Caso: Tardanza ---
        // Requiere hora de entrada explícita y que sea al menos 16 min después del inicio.
        } elseif ($statusId === $lateStatusId) {
            if (!array_key_exists('entry_hour', $data) || empty($data['entry_hour'])) {
                return ['error' => true, 'code' => 422, 'message' => 'entry_hour es requerido para tardanza'];
            }

            $entry = Carbon::createFromFormat('H:i', $data['entry_hour']);

            if ($entry->lt($lateFrom)) {
                return [
                    'error' => true,
                    'code' => 422,
                    'message' => 'Para tardanza, la hora de entrada debe ser desde 16 minutos después del inicio de clase',
                ];
            }

            $attendanceData['entry_hour'] = $entry->format('H:i:s');

        // --- Caso: Presente u otro estado ---
        // Se registra la hora actual como hora de entrada.
        } else {
            $attendanceData['entry_hour'] = now()->format('H:i:s');
        }

        // Si se enviaron observaciones, se incluyen en el update.
        if (array_key_exists('observations', $data)) {
            $attendanceData['observations'] = $data['observations'];
        }

        // Calcula las horas ausentes con la hora de entrada final determinada arriba.
        $attendanceData['absent_hours'] = $this->calculateAbsentHours([
            'real_class_id' => $attendance->real_class_id,
            'attendance_status_id' => $statusId,
            'entry_hour' => $attendanceData['entry_hour'],
        ]);

        $attendance->update($attendanceData);

        // Si el estado resultante es "ausente", dispara el evento para auditoría/notificaciones.
        // Se usa fresh() para leer el estado real desde BD y no confiar en el objeto en memoria.
        if ($attendance->fresh()->attendance_status_id === $absentStatusId) {
            event(new ResourceChanged(
                'updated',
                Attendance::class,
                $attendance->id,
                $attendance->apprentice_id,
                'Inasistencia',
            ));
        }

        $updated = $attendance->fresh();
        $summary = $this->summaryByRealClassId((int) $updated->real_class_id);

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencia actualizada correctamente',
            'data' => [
                'id' => $updated->id,
                'computed' => [
                    'entry_hour' => $updated->entry_hour,
                    'absent_hours' => $updated->absent_hours,
                ],
            ],
            'summary' => $summary,
        ];
    }

    /**
     * Calcula las horas ausentes de un aprendiz según su estado y hora de entrada.
     *
     * - Ausente: horas ausentes = duración total de la clase.
     * - Tardanza: horas ausentes = minutos tarde redondeados hacia arriba en horas, con tope en la duración.
     * - Otros estados: horas ausentes = 0.
     *
     * @param  array  $data  Debe incluir real_class_id, attendance_status_id y entry_hour.
     * @return int
     */
    private function calculateAbsentHours($data)
    {
        $absentHours = 0;

        // Carga la clase real con su sesión para obtener la duración total.
        $realClass = RealClass::with('scheduleSession')->find($data['real_class_id']);

        // Redondea la duración hacia arriba para trabajar en horas enteras.
        $maxHours = (int) ceil($realClass->scheduleSession->durationSession);

        $statuses = $this->getStatusIds();
        $absentStatusId = (int) ($statuses['absent'] ?? 0);
        $lateStatusId   = (int) ($statuses['late'] ?? 0);

        switch ((int) $data['attendance_status_id']) {

            // Ausente: cuenta toda la duración de la clase como horas perdidas.
            case $absentStatusId:
                $absentHours = $maxHours;
                break;

            // Tardanza: calcula los minutos de retraso y los convierte a horas, con tope en maxHours.
            case $lateStatusId:
                $entryHour  = Carbon::parse($data['entry_hour']);
                $startClass = Carbon::parse($realClass->start_hour);

                // Si entró antes o exactamente a la hora de inicio, no hay horas ausentes.
                if ($entryHour->lte($startClass)) {
                    $absentHours = 0;
                    break;
                }

                $minutesLate = $startClass->diffInMinutes($entryHour, true);

                // ceil para redondear hacia arriba; min para no superar la duración total de la clase.
                $absentHours = min((int) ceil($minutesLate / 60), $maxHours);
                break;

            // Cualquier otro estado (presente, salida anticipada, etc.): sin horas ausentes.
        }

        return $absentHours;
    }

    /**
     * Obtiene los IDs de los estados de asistencia base en una sola consulta.
     *
     * Centraliza el acceso a los estados para evitar múltiples queries repetidas.
     *
     * @return array  Mapa de code => id (ej: ['present' => 1, 'absent' => 2, ...]).
     */
    private function getStatusIds()
    {
        return AttendanceStatus::whereIn('code', [
            'present',
            'absent',
            'late',
            'early_exit',
            'unregistered'
        ])->pluck('id', 'code')->toArray();
    }

    /**
     * Calcula el resumen de asistencias de una clase real directamente desde la BD.
     *
     * Usa un JOIN con GROUP BY para obtener los totales por estado en una sola consulta,
     * en lugar de traer todos los registros y contarlos en PHP.
     *
     * @param  int  $realClassId  ID de la clase real.
     * @return array
     */
    private function summaryByRealClassId(int $realClassId)
    {
        // Query optimizado: agrupa en BD y trae solo los conteos, no los registros completos.
        $rows = Attendance::query()
            ->selectRaw('attendance_statuses.code as code, COUNT(*) as total')
            ->join('attendance_statuses', 'attendance_statuses.id', '=', 'attendances.attendance_status_id')
            ->where('attendances.real_class_id', $realClassId)
            ->groupBy('attendance_statuses.code')
            ->get();

        // Convierte el resultado en un mapa code => total para acceso directo.
        $countsByCode = $rows->pluck('total', 'code')->toArray();

        // Garantiza que todos los estados posibles aparezcan en el resumen aunque tengan 0.
        foreach (['present', 'absent', 'excused_absence', 'late', 'early_exit', 'unregistered'] as $c) {
            if (!array_key_exists($c, $countsByCode)) $countsByCode[$c] = 0;
        }

        return [
            'total' => array_sum($countsByCode),
            'counts_by_code' => $countsByCode,
        ];
    }

    /**
     * Registra la entrada de un aprendiz mediante escaneo de número de documento.
     *
     * Determina automáticamente si el aprendiz llega puntual o tarde según la hora actual,
     * y actualiza su asistencia en la clase real activa de su ficha.
     *
     * @param  array  $data  Debe contener document_number.
     * @return array
     */
    public function scanCheckIn($data)
    {
        $documentNumber = $data['document_number'];

        // Busca el aprendiz activo con ese número de documento.
        $apprentice = Apprentice::query()
            ->where('document_number', $documentNumber)
            ->where('status_id', 1)
            ->first();

        if (!$apprentice) {
            return ['error' => true, 'code' => 404, 'message' => 'Aprendiz no encontrado o inactivo'];
        }

        $now = now();

        // Busca la clase real disponible para escaneo en este momento para la ficha del aprendiz.
        $realClass = $this->findScannableRealClassForFicha((int) $apprentice->ficha_id, $now);
        if (!$realClass) {
            return ['error' => true, 'code' => 404, 'message' => 'No hay clase real disponible para registrar en este momento'];
        }

        // Obtiene los IDs de los estados "presente" y "tardanza" para el check-in.
        $statusIds = AttendanceStatus::whereIn('code', ['present', 'late'])
            ->pluck('id', 'code')
            ->toArray();

        $presentId = (int) ($statusIds['present'] ?? 0);
        $lateId    = (int) ($statusIds['late'] ?? 0);

        if (!$presentId || !$lateId) {
            return ['error' => true, 'code' => 500, 'message' => 'Faltan estados base (present/late). Ejecuta el seeder.'];
        }

        // Determina si el aprendiz llega tarde (más de 15 minutos después del inicio).
        $start = Carbon::parse($realClass->start_hour);
        $lateFrom = $start->copy()->addMinutes(16);
        $isLate = $now->gte($lateFrom);
        $statusId = $isLate ? $lateId : $presentId;

        // Busca el registro de asistencia base creado al abrir la clase real.
        $attendance = Attendance::query()
            ->where('real_class_id', $realClass->id)
            ->where('apprentice_id', $apprentice->id)
            ->first();

        if (!$attendance) {
            return ['error' => true, 'code' => 404, 'message' => 'No existe asistencia base para esta clase real'];
        }

        // Evita que un aprendiz registre entrada dos veces.
        if (!is_null($attendance->entry_hour)) {
            return ['error' => true, 'code' => 422, 'message' => 'Este aprendiz ya registró entrada'];
        }

        $entryHour = $now->format('H:i:s');

        // Calcula las horas ausentes según si llegó puntual o tarde.
        $absentHours = $this->calculateAbsentHours([
            'real_class_id' => $realClass->id,
            'attendance_status_id' => $statusId,
            'entry_hour' => $entryHour,
        ]);

        // Actualiza el registro de asistencia con el estado, hora de entrada y horas ausentes.
        $attendance->update([
            'attendance_status_id' => $statusId,
            'entry_hour' => $entryHour,
            'absent_hours' => $absentHours,
        ]);

        return [
            'error' => false,
            'code' => 200,
            'message' => $isLate ? 'Entrada registrada: tardanza' : 'Entrada registrada: presente',
        ];
    }

    /**
     * Encuentra la clase real disponible para escaneo en un momento dado para una ficha.
     *
     * Una clase real es escaneable si la hora actual está entre:
     * - 30 minutos antes del inicio (apertura del check-in).
     * - 1 hora antes del fin (cierre del check-in para evitar registros al final de clase).
     *
     * @param  int     $fichaId  ID de la ficha del aprendiz.
     * @param  Carbon  $now      Momento actual para evaluar la ventana de escaneo.
     * @return RealClass|null    La clase real escaneable, o null si no hay ninguna.
     */
    private function findScannableRealClassForFicha(int $fichaId, Carbon $now)
    {
        // Carga la ficha con su término activo, horario y las clases reales del día actual.
        $ficha = Ficha::query()
            ->with([
                'currentFichaTerm.schedule.scheduleSessions.realClasses' => function ($q) use ($now) {
                    // Filtra directamente en la consulta para traer solo las clases de hoy.
                    $q->whereDate('execution_date', $now->toDateString());
                },
            ])
            ->find($fichaId);

        // Si la ficha no tiene término activo o no tiene horario asignado, no hay clase posible.
        if (!$ficha?->currentFichaTerm?->schedule) {
            return null;
        }

        // Aplana las clases reales de todas las sesiones del horario en una sola colección.
        $realClasses = $ficha->currentFichaTerm->schedule
            ->scheduleSessions
            ->flatMap(fn($ss) => $ss->realClasses);

        // Retorna la primera clase (ordenada por hora de inicio) que esté dentro de la ventana de escaneo.
        return $realClasses
            ->sortBy(fn($rc) => $rc->start_hour)
            ->first(function ($rc) use ($now) {
                $start = Carbon::parse($rc->start_hour);
                $end   = Carbon::parse($rc->end_hour);

                // Check-in abre 30 minutos antes del inicio.
                $openFrom  = $start->copy()->subMinutes(30);
                // Check-in cierra 1 hora antes del fin para evitar registros tardíos al final.
                $blockFrom = $end->copy()->subHour();

                return $now->between($openFrom, $blockFrom, true);
            });
    }
}