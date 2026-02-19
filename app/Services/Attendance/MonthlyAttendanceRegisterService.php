<?php

namespace App\Services\Attendance;

use App\Models\Apprentice;
use App\Models\Attendance;
use App\Models\Ficha;
use App\Models\NoClassDay;
use App\Models\RealClass;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Servicio para generar el registro mensual de asistencias de una ficha.
 *
 * Construye una estructura tipo planilla con días del mes como columnas,
 * aprendices como filas, y las marcas de asistencia por franja horaria (slot).
 * Diferencia entre jornadas DIURNA y NOCTURNA para determinar qué días
 * y franjas son válidos.
 */
class MonthlyAttendanceRegisterService
{
    /**
     * Genera el registro mensual de asistencias de una ficha.
     *
     * El payload resultante incluye:
     * - Días hábiles del mes según la jornada de la ficha.
     * - Clases reales agrupadas por fecha y franja.
     * - Marcas de asistencia de cada aprendiz por fecha y franja.
     * - Resumen de conteos por estado.
     *
     * @param  array  $data  Debe contener ficha_id, year, month y opcionalmente timezone.
     * @return array
     */
    public function monthlyRegister(array $data): array
    {
        $fichaId = (int) $data['ficha_id'];
        $year    = (int) $data['year'];
        $month   = (int) $data['month'];

        // Zona horaria configurable; por defecto Colombia.
        $tz = $data['timezone'] ?? 'America/Bogota';

        // Define el rango del mes completo en la zona horaria indicada.
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // Carga la ficha con su programa y jornada para determinar las reglas del calendario.
        $ficha = Ficha::with(['trainingProgram', 'shift'])->find($fichaId);
        if (!$ficha) {
            return ['error' => true, 'code' => 404, 'message' => 'Ficha no encontrada'];
        }

        // Determina si la jornada es nocturna, lo que cambia los días y franjas válidas.
        $isNocturna = $this->isNocturna($ficha);

        // La jornada nocturna incluye sábados; la diurna no.
        $includeSaturday = $isNocturna;

        // Define las franjas horarias válidas según la jornada:
        // Diurna: mañana (am) y tarde (pm). Nocturna: tarde (pm) y noche (nt).
        $slots = $isNocturna
            ? [
                ['code' => 'pm', 'label' => 'Tarde'],
                ['code' => 'nt', 'label' => 'Noche'],
              ]
            : [
                ['code' => 'am', 'label' => 'Mañana'],
                ['code' => 'pm', 'label' => 'Tarde'],
              ];

        $slotCodes  = array_map(fn($s) => $s['code'], $slots);

        // Índice de slots válidos para búsquedas O(1) en lugar de recorrer el array.
        $slotsIndex = array_fill_keys($slotCodes, true);

        // -----------------------------------------------------------------------
        // PASO 1: Construir la lista de días hábiles del mes.
        // Siempre se excluye el domingo; el sábado solo se incluye en nocturna.
        // -----------------------------------------------------------------------
        $days = [];
        $period = CarbonPeriod::between($start->toDateString(), $end->toDateString());
        foreach ($period as $date) {
            $dow = strtolower($date->format('D')); // Abrevia el día: mon, tue, wed...
            if ($dow === 'sun') continue;
            if (!$includeSaturday && $dow === 'sat') continue;
            $days[] = $date->format('Y-m-d');
        }

        // -----------------------------------------------------------------------
        // PASO 2: Cargar días sin clase (NoClassDay) y construir day_info_by_date.
        // Cada día queda marcado como 'instructional' o 'no_class_day'.
        // -----------------------------------------------------------------------
        $noClassDays = NoClassDay::with('reason')
            ->where('ficha_id', $fichaId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            // keyBy permite buscar por fecha en O(1) más adelante.
            ->keyBy(fn($ncd) => Carbon::parse($ncd->date)->format('Y-m-d'));

        $dayInfoByDate = [];
        foreach ($days as $d) {
            if ($noClassDays->has($d)) {
                // Día sin clase: guarda el motivo y observaciones para mostrarlo en la planilla.
                $ncd = $noClassDays->get($d);
                $dayInfoByDate[$d] = [
                    'day_state' => 'no_class_day',
                    'reason' => $ncd->reason ? ['id' => (int)$ncd->reason->id, 'name' => $ncd->reason->name] : null,
                    'observations' => $ncd->observations ?? null,
                ];
            } else {
                $dayInfoByDate[$d] = ['day_state' => 'instructional'];
            }
        }

        // -----------------------------------------------------------------------
        // PASO 3: Inicializar la estructura classes_by_date_slot con arrays vacíos.
        // Esto garantiza que todas las celdas existan aunque no haya clases.
        // -----------------------------------------------------------------------
        $classesByDateSlot = [];
        foreach ($days as $d) {
            foreach ($slotCodes as $sc) {
                $classesByDateSlot[$d][$sc] = [];
            }
        }

        // -----------------------------------------------------------------------
        // PASO 4: Cargar aprendices activos e inicializar marks_by_date_slot.
        // Cada aprendiz tiene una celda vacía por cada día x franja para llenar luego.
        // -----------------------------------------------------------------------
        $apprentices = Apprentice::with('profile')
            ->where('ficha_id', $fichaId)
            ->where('status_id', 1)
            ->get();

        $apprenticeRows = [];
        foreach ($apprentices as $ap) {
            $p = $ap->profile;
            $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

            // Inicializa las marcas de asistencia en vacío para todos los días y franjas.
            $marksByDateSlot = [];
            foreach ($days as $d) {
                foreach ($slotCodes as $sc) {
                    $marksByDateSlot[$d][$sc] = [];
                }
            }

            // Indexado por ID del aprendiz para relacionar asistencias en O(1) más adelante.
            $apprenticeRows[$ap->id] = [
                'id' => (int)$ap->id,
                'full_name' => $fullName !== '' ? $fullName : "Aprendiz #{$ap->id}",
                'document_number' => $ap->document_number ?? null,
                'marks_by_date_slot' => $marksByDateSlot,
            ];
        }

        // -----------------------------------------------------------------------
        // PASO 4.1: Cargar aprendices inactivos para mostrarlos separados en la planilla.
        // No tienen marcas de asistencia; solo se listan como referencia.
        // -----------------------------------------------------------------------
        $inactiveApprentices = Apprentice::with('profile')
            ->where('ficha_id', $fichaId)
            ->where('status_id', 2)
            ->orderBy('document_number')
            ->get();

        $inactiveApprenticeRows = [];
        foreach ($inactiveApprentices as $ap) {
            $p = $ap->profile;
            $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

            $inactiveApprenticeRows[] = [
                'id' => (int)$ap->id,
                'document_number' => $ap->document_number ?? null,
                'full_name' => $fullName !== '' ? $fullName : "Aprendiz #{$ap->id}",
            ];
        }

        // -----------------------------------------------------------------------
        // PASO 5: Cargar clases reales del mes y ubicarlas en classes_by_date_slot.
        //
        // display_date: si la clase fue reprogramada, se muestra en la fecha original
        // (original_date) en lugar de la fecha de ejecución real (execution_date).
        // Esto mantiene coherencia visual en la planilla aunque la clase se haya movido.
        // -----------------------------------------------------------------------
        $realClasses = RealClass::query()
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
            ])
            ->with([
                'classType:id,name',
                'classroom:id,name',
                'timeSlot:id,code,name',
                'instructor.profile:user_id,first_name,last_name',
                // timeSlot de la sesión de horario: determina si es MORNING/AFTERNOON/NIGHT.
                'scheduleSession.timeSlot:id,code',
            ])
            // Filtra solo las clases que pertenecen a esta ficha (navegando la relación completa).
            ->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $fichaId))
            ->whereBetween('execution_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // Acumula los IDs de clases reales que efectivamente se ubican en la grilla.
        $realClassIdsUsed = [];

        foreach ($realClasses as $rc) {
            // Fecha en la que se muestra la clase (original si fue reprogramada).
            $displayDate = $this->displayDate($rc);

            // Si el displayDate cae fuera de los días hábiles calculados, se ignora.
            if (!isset($dayInfoByDate[$displayDate])) continue;

            // Las clases en días sin clase no se muestran en la planilla.
            if (($dayInfoByDate[$displayDate]['day_state'] ?? '') === 'no_class_day') continue;

            // Traduce el código de franja del horario (MORNING/AFTERNOON/NIGHT) al código de slot (am/pm/nt).
            $resolvedSlot = $this->resolveSlotFromScheduleTimeSlotCode(
                $rc->scheduleSession?->timeSlot?->code,
                $isNocturna
            );

            // Si el slot no aplica a esta jornada (ej: MORNING en nocturna), se descarta.
            if (!$resolvedSlot || !isset($slotsIndex[$resolvedSlot])) continue;

            $realClassIdsUsed[] = $rc->id;

            // Agrega la clase real a la celda correspondiente (fecha x franja).
            $classesByDateSlot[$displayDate][$resolvedSlot][] = [
                'real_class_id' => (int)$rc->id,

                'display_date' => $displayDate,
                'execution_date' => $rc->execution_date ? Carbon::parse($rc->execution_date)->format('Y-m-d') : null,
                'original_date' => $rc->original_date ? Carbon::parse($rc->original_date)->format('Y-m-d') : null,

                'slot_source' => [
                    'schedule_session_id' => (int)($rc->schedule_session_id ?? 0),
                    'schedule_time_slot_code' => $rc->scheduleSession?->timeSlot?->code,
                    'resolved_slot' => $resolvedSlot,
                    'resolved_slot_label' => $this->slotLabel($resolvedSlot, $slots),
                ],

                'class_type' => $rc->classType ? [
                    'id' => (int)$rc->classType->id,
                    'name' => $rc->classType->name,
                ] : null,

                'instructor' => [
                    'id' => (int)($rc->instructor_id ?? 0),
                    'full_name' => $this->instructorName($rc),
                ],

                'classroom' => $rc->classroom ? [
                    'id' => (int)$rc->classroom->id,
                    'name' => $rc->classroom->name,
                ] : null,

                // Recorta a HH:MM, descartando los segundos para la visualización.
                'real_time' => [
                    'start_hour' => $rc->start_hour ? substr($rc->start_hour, 0, 5) : null,
                    'end_hour' => $rc->end_hour ? substr($rc->end_hour, 0, 5) : null,
                ],

                'real_timeslot' => $rc->timeSlot ? [
                    'code' => $rc->timeSlot->code,
                    'name' => $rc->timeSlot->name,
                ] : null,

                'observations' => $rc->observations ?? null,
            ];
        }

        // -----------------------------------------------------------------------
        // PASO 6: Cargar asistencias y distribuirlas en marks_by_date_slot de cada aprendiz.
        //
        // Se hace en una sola consulta sobre los IDs de clases usadas para evitar N+1.
        // Luego se itera sobre las asistencias y se ubica cada una en la celda correcta
        // del aprendiz correspondiente.
        // -----------------------------------------------------------------------
        $countsByCode = [
            'present' => 0,
            'absent' => 0,
            'excused_absence' => 0,
            'late' => 0,
            'early_exit' => 0,
            'unregistered' => 0,
        ];

        if (!empty($realClassIdsUsed) && !empty($apprenticeRows)) {
            // Una sola query trae todas las asistencias del mes para las clases usadas.
            $attendances = Attendance::query()
                ->select([
                    'id',
                    'real_class_id',
                    'apprentice_id',
                    'attendance_status_id',
                    'entry_hour',
                    'absent_hours',
                    'observations',
                ])
                ->with([
                    'attendanceStatus:id,code,name',
                    // Necesita la relación hasta timeSlot para resolver el slot de cada asistencia.
                    'realClass:id,schedule_session_id,execution_date,original_date',
                    'realClass.scheduleSession.timeSlot:id,code',
                ])
                // array_unique para no duplicar IDs si una clase aparece en varios slots.
                ->whereIn('real_class_id', array_values(array_unique($realClassIdsUsed)))
                ->get();

            foreach ($attendances as $a) {
                $apId = (int)$a->apprentice_id;

                // Solo procesa aprendices activos que estén en la planilla.
                if (!isset($apprenticeRows[$apId])) continue;

                $rc = $a->realClass;
                if (!$rc) continue;

                // Replica la misma lógica de displayDate para ubicar en la celda correcta.
                $displayDate = $rc->original_date
                    ? Carbon::parse($rc->original_date)->format('Y-m-d')
                    : Carbon::parse($rc->execution_date)->format('Y-m-d');

                if (!isset($dayInfoByDate[$displayDate])) continue;
                if (($dayInfoByDate[$displayDate]['day_state'] ?? '') === 'no_class_day') continue;

                $resolvedSlot = $this->resolveSlotFromScheduleTimeSlotCode(
                    $rc->scheduleSession?->timeSlot?->code,
                    $isNocturna
                );

                if (!$resolvedSlot || !isset($slotsIndex[$resolvedSlot])) continue;

                $status = $a->attendanceStatus?->name ?? 'Sin Registrar';
                $code = $a->attendanceStatus?->code ?? 'unknow';

                // Acumula el conteo global por código de estado para el resumen.
                if (isset($countsByCode[$status])) $countsByCode[$status]++;

                // Agrega la marca de asistencia a la celda del aprendiz (fecha x franja).
                $apprenticeRows[$apId]['marks_by_date_slot'][$displayDate][$resolvedSlot][] = [
                    'attendance_id' => (int)$a->id,
                    'real_class_id' => (int)$a->real_class_id,
                    'status' => $code,
                    'status_name' => $status,
                    'observations' => $a->observations ?? null,
                    'entry_hour' => $a->entry_hour ? substr($a->entry_hour, 0, 5) : null,
                    'absent_hours' => (int)($a->absent_hours ?? 0),
                ];
            }
        }

        // Leyenda de estados para que el frontend pueda renderizar las etiquetas.
        $legend = [
            'present' => 'Asistencia',
            'absent' => 'Inasistencia',
            'late' => 'Tardanza',
            'early_exit' => 'Salida anticipada',
            'excused_absence' => 'Justificada',
            'unregistered' => 'Sin registrar',
            'no_class_day' => 'Sin clase (todo el día)',
        ];

        // Construye el payload completo con toda la información de la planilla.
        $payload = [
            'ficha' => [
                'id' => (int)$ficha->id,
                'ficha_number' => $ficha->ficha_number ?? $ficha->fichanumber ?? null,
                'shift_mode' => $isNocturna ? 'NOCTURNA' : 'DIURNA',
                'training_program' => $ficha->trainingProgram ? [
                    'id' => (int)$ficha->trainingProgram->id,
                    'name' => $ficha->trainingProgram->name,
                ] : null,
            ],
            'period' => [
                'year' => $year,
                'month' => $month,
                'timezone' => $tz,
            ],
            'calendar_rules' => [
                'exclude_weekdays' => ['sun'],
                'include_saturday' => $includeSaturday,
            ],
            'slots' => $slots,
            'days' => $days,
            'day_info_by_date' => $dayInfoByDate,
            'legend' => $legend,
            'classes_by_date_slot' => $classesByDateSlot,
            'apprentices' => array_values($apprenticeRows),
            'inactive_apprentices' => $inactiveApprenticeRows,
        ];

        $summary = [
            'total_apprentices' => count($apprenticeRows),
            'total_inactive_apprentices' => count($inactiveApprenticeRows),
            'total_marks' => array_sum($countsByCode),
            'counts_by_code' => $countsByCode,
        ];

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Registro mensual de asistencias obtenido correctamente',
            'data' => $payload,
            'paginate' => [],
            'summary' => $summary,
        ];
    }

    /**
     * Determina si la jornada de la ficha es nocturna.
     *
     * Primero intenta leer el código del turno (DIURNA/NOCTURNA).
     * Si no existe, cae en el nombre y busca la palabra "noct" como fallback.
     *
     * @param  Ficha  $ficha
     * @return bool
     */
    private function isNocturna(Ficha $ficha): bool
    {
        $code = strtoupper($ficha->shift?->code ?? '');
        if ($code !== '') return $code === 'NOCTURNA';

        // Fallback: busca "noct" en el nombre del turno si el código no está definido.
        $name = strtolower($ficha->shift?->name ?? '');
        return str_contains($name, 'noct');
    }

    /**
     * Retorna la fecha de visualización de una clase real en la planilla.
     *
     * Si la clase fue reprogramada, se muestra en la fecha original para que
     * la planilla refleje cuándo se debía dar la clase, no cuándo ocurrió.
     *
     * @param  RealClass  $rc
     * @return string  Fecha en formato Y-m-d.
     */
    private function displayDate(RealClass $rc): string
    {
        // Prioriza original_date (fecha planeada); si no existe, usa execution_date.
        $d = $rc->original_date ?: $rc->execution_date;
        return Carbon::parse($d)->format('Y-m-d');
    }

    /**
     * Traduce el código de franja del horario (MORNING/AFTERNOON/NIGHT) al slot de la planilla (am/pm/nt).
     *
     * La traducción varía según la jornada:
     * - Diurna: MORNING → am, AFTERNOON → pm. NIGHT se descarta.
     * - Nocturna: AFTERNOON → pm, NIGHT → nt. MORNING se descarta.
     *
     * @param  string|null  $scheduleTimeSlotCode  Código del timeSlot de la sesión de horario.
     * @param  bool         $isNocturna            Si la ficha es de jornada nocturna.
     * @return string|null  Código de slot resuelto, o null si no aplica a esta jornada.
     */
    private function resolveSlotFromScheduleTimeSlotCode(?string $scheduleTimeSlotCode, bool $isNocturna): ?string
    {
        if (!$scheduleTimeSlotCode) return null;
        $code = strtoupper($scheduleTimeSlotCode);

        if (!$isNocturna) {
            return match ($code) {
                'MORNING'   => 'am',
                'AFTERNOON' => 'pm',
                default     => null, // NIGHT no aplica a jornada diurna.
            };
        }

        return match ($code) {
            'AFTERNOON' => 'pm',
            'NIGHT'     => 'nt',
            default     => null, // MORNING no aplica a jornada nocturna.
        };
    }

    /**
     * Retorna la etiqueta legible de un slot dado su código.
     *
     * @param  string  $slotCode  Código del slot (am, pm, nt).
     * @param  array   $slots     Lista de slots con code y label.
     * @return string  Label del slot, o el propio código si no se encuentra.
     */
    private function slotLabel(string $slotCode, array $slots): string
    {
        foreach ($slots as $s) {
            if (($s['code'] ?? '') === $slotCode) return (string)$s['label'];
        }
        // Si no se encuentra, retorna el código como fallback para no dejar vacío.
        return $slotCode;
    }

    /**
     * Construye el nombre completo del instructor de una clase real.
     *
     * @param  RealClass  $rc
     * @return string  Nombre completo, o 'Sin instructor' si no hay perfil asociado.
     */
    private function instructorName(RealClass $rc): string
    {
        $p = $rc->instructor?->profile;
        $full = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
        return $full !== '' ? $full : 'Sin instructor';
    }
}