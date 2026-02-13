<?php

namespace App\Services\Attendance;

use App\Models\Apprentice;
use App\Models\Attendance;
use App\Models\Ficha;
use App\Models\NoClassDay;
use App\Models\RealClass;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MonthlyAttendanceRegisterService
{
    public function monthlyRegister(array $data): array
    {
        $fichaId = (int) $data['ficha_id'];
        $year    = (int) $data['year'];
        $month   = (int) $data['month'];

        $tz = $data['timezone'] ?? 'America/Bogota';

        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $ficha = Ficha::with(['trainingProgram', 'shift'])->find($fichaId);
        if (!$ficha) {
            return ['error' => true, 'code' => 404, 'message' => 'Ficha no encontrada'];
        }

        // Jornada => reglas
        $isNocturna = $this->isNocturna($ficha);
        $includeSaturday = $isNocturna;

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
        $slotsIndex = array_fill_keys($slotCodes, true);

        // 1) days[] (domingo siempre; sábado depende de jornada)
        $days = [];
        $period = CarbonPeriod::between($start->toDateString(), $end->toDateString());
        foreach ($period as $date) {
            $dow = strtolower($date->format('D')); // mon,tue,wed...
            if ($dow === 'sun') continue;
            if (!$includeSaturday && $dow === 'sat') continue;
            $days[] = $date->format('Y-m-d');
        }

        // 2) NoClassDay => day_info_by_date
        $noClassDays = NoClassDay::with('reason')
            ->where('ficha_id', $fichaId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($ncd) => Carbon::parse($ncd->date)->format('Y-m-d'));

        $dayInfoByDate = [];
        foreach ($days as $d) {
            if ($noClassDays->has($d)) {
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

        // 3) Inicializar classes_by_date_slot con []
        $classesByDateSlot = [];
        foreach ($days as $d) {
            foreach ($slotCodes as $sc) {
                $classesByDateSlot[$d][$sc] = [];
            }
        }

        // 4) Aprendices + marks_by_date_slot con []
        $apprentices = Apprentice::with('profile')
            ->where('ficha_id', $fichaId)
            ->where('status_id', 1)
            ->get();

        $apprenticeRows = [];
        foreach ($apprentices as $ap) {
            $p = $ap->profile;
            $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));

            $marksByDateSlot = [];
            foreach ($days as $d) {
                foreach ($slotCodes as $sc) {
                    $marksByDateSlot[$d][$sc] = [];
                }
            }

            $apprenticeRows[$ap->id] = [
                'id' => (int)$ap->id,
                'full_name' => $fullName !== '' ? $fullName : "Aprendiz #{$ap->id}",
                'document_number' => $ap->document_number ?? null,
                'marks_by_date_slot' => $marksByDateSlot,
            ];
        }

        // 5) RealClass del mes (display_date = original_date ?? execution_date)
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
                'timeSlot:id,code,name',              // franja real (del real class)
                'instructor.profile:user_id,first_name,last_name',
                'scheduleSession.timeSlot:id,code',   // MORNING/AFTERNOON/NIGHT (del schedule)
            ])
            ->whereHas('scheduleSession.schedule.fichaTerm.ficha', fn($q) => $q->where('id', $fichaId))
            ->whereBetween('execution_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $realClassIdsUsed = [];

        foreach ($realClasses as $rc) {
            $displayDate = $this->displayDate($rc);

            // fuera de grilla
            if (!isset($dayInfoByDate[$displayDate])) continue;

            // si es no_class_day, se ignora
            if (($dayInfoByDate[$displayDate]['day_state'] ?? '') === 'no_class_day') continue;

            // slot desde schedule session
            $resolvedSlot = $this->resolveSlotFromScheduleTimeSlotCode(
                $rc->scheduleSession?->timeSlot?->code,
                $isNocturna
            );

            // no aplica => ignorar
            if (!$resolvedSlot || !isset($slotsIndex[$resolvedSlot])) continue;

            $realClassIdsUsed[] = $rc->id;

            $classesByDateSlot[$displayDate][$resolvedSlot][] = [
                'real_class_id' => (int)$rc->id,

                'display_date' => $displayDate,
                'execution_date' => $rc->execution_date ? Carbon::parse($rc->execution_date)->format('Y-m-d') : null,
                'original_date' => $rc->original_date ? Carbon::parse($rc->original_date)->format('Y-m-d') : null,

                'slot_source' => [
                    'schedule_session_id' => (int)($rc->schedule_session_id ?? 0),
                    'schedule_time_slot_code' => $rc->scheduleSession?->timeSlot?->code, // MORNING/AFTERNOON/NIGHT
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

        // 6) Attendance => marks_by_date_slot
        $countsByCode = [
            'present' => 0,
            'absent' => 0,
            'excused_absence' => 0,
            'late' => 0,
            'early_exit' => 0,
            'unregistered' => 0,
        ];

        if (!empty($realClassIdsUsed) && !empty($apprenticeRows)) {
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
                    'realClass:id,schedule_session_id,execution_date,original_date',
                    'realClass.scheduleSession.timeSlot:id,code',
                ])
                ->whereIn('real_class_id', array_values(array_unique($realClassIdsUsed)))
                ->get();

            foreach ($attendances as $a) {
                $apId = (int)$a->apprentice_id;
                if (!isset($apprenticeRows[$apId])) continue;

                $rc = $a->realClass;
                if (!$rc) continue;

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

                $code = $a->attendanceStatus?->code ?? 'unknown';
                if (isset($countsByCode[$code])) $countsByCode[$code]++;

                $apprenticeRows[$apId]['marks_by_date_slot'][$displayDate][$resolvedSlot][] = [
                    'attendance_id' => (int)$a->id,
                    'real_class_id' => (int)$a->real_class_id,
                    'status' => $code,
                    'observations' => $a->observations ?? null,
                    'entry_hour' => $a->entry_hour ? substr($a->entry_hour, 0, 5) : null,
                    'absent_hours' => (int)($a->absent_hours ?? 0),
                ];
            }
        }

        $legend = [
            'present' => 'Asistencia',
            'absent' => 'Inasistencia',
            'late' => 'Tardanza',
            'early_exit' => 'Salida anticipada',
            'excused_absence' => 'Justificada',
            'unregistered' => 'Sin registrar',
            'no_class_day' => 'Sin clase (todo el día)',
        ];

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
        ];

        $summary = [
            'total_apprentices' => count($apprenticeRows),
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

    private function isNocturna(Ficha $ficha): bool
    {
        // Ideal: shifts.code = DIURNA|NOCTURNA
        $code = strtoupper($ficha->shift?->code ?? '');
        if ($code !== '') return $code === 'NOCTURNA';

        $name = strtolower($ficha->shift?->name ?? '');
        return str_contains($name, 'noct');
    }

    private function displayDate(RealClass $rc): string
    {
        $d = $rc->original_date ?: $rc->execution_date;
        return Carbon::parse($d)->format('Y-m-d');
    }

    private function resolveSlotFromScheduleTimeSlotCode(?string $scheduleTimeSlotCode, bool $isNocturna): ?string
    {
        if (!$scheduleTimeSlotCode) return null;
        $code = strtoupper($scheduleTimeSlotCode);

        if (!$isNocturna) {
            // diurna
            return match ($code) {
                'MORNING' => 'am',
                'AFTERNOON' => 'pm',
                default => null, // NIGHT => ignore
            };
        }

        // nocturna
        return match ($code) {
            'AFTERNOON' => 'pm',
            'NIGHT' => 'nt',
            default => null, // MORNING => ignore
        };
    }

    private function slotLabel(string $slotCode, array $slots): string
    {
        foreach ($slots as $s) {
            if (($s['code'] ?? '') === $slotCode) return (string)$s['label'];
        }
        return $slotCode;
    }

    private function instructorName(RealClass $rc): string
    {
        $p = $rc->instructor?->profile;
        $full = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
        return $full !== '' ? $full : 'Sin instructor';
    }
}