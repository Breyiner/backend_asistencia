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

class AttendanceService
{
    public function getAll()
    {
        $attendances = Attendance::with(['attendanceStatus', 'realClass'])->get();

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencias obtenidas correctamente',
            'data' => $attendances
        ];
    }

    public function getById($id)
    {
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

    public function byClassRealId($realClassId)
    {
        $attendances = Attendance::with([
            'attendanceStatus:id,name,code',
            'apprentice.profile:id,user_id,first_name,last_name',
        ])
            ->where('real_class_id', $realClassId)
            ->get()
            ->sortBy(fn($a) => $a->apprentice?->profile?->last_name ?? '')
            ->values()
            ->map(function ($a) {
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

        $countsByCode = $attendances
            ->groupBy(fn($a) => $a['attendance_status']['code'] ?? 'unknown')
            ->map(fn($group) => $group->count())
            ->toArray();

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

    public function create($data)
    {
        if (!$data['entry_hour']) {
            $data['entry_hour'] = now()->format('H:i');
        }

        $data['absent_hours'] = $this->calculateAbsentHours($data);

        Attendance::create($data);

        return [
            'error' => false,
            'code' => 201,
            'message' => 'Asistencia registrada correctamente',
        ];
    }

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

    public function createdByFicha($fichaId, $realClassId)
    {
        $apprentices = Apprentice::where('ficha_id', $fichaId)
            ->where('status_id', 1)
            ->get();

        $unregisteredStatusId = (int) AttendanceStatus::where('code', 'unregistered')->value('id');

        if (!$unregisteredStatusId) {
            return;
        }

        foreach ($apprentices as $apprentice) {
            Attendance::create([
                'real_class_id' => $realClassId,
                'apprentice_id' => $apprentice->id,
                'attendance_status_id' => $unregisteredStatusId
            ]);
        }
    }

    public function update($data, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return ['error' => true, 'code' => 404, 'message' => 'Asistencia no encontrada'];
        }

        if (!array_key_exists('attendance_status_id', $data)) {
            return ['error' => true, 'code' => 422, 'message' => 'attendance_status_id es requerido'];
        }

        $statuses = $this->getStatusIds();
        $absentStatusId       = (int) ($statuses['absent'] ?? 0);
        $lateStatusId         = (int) ($statuses['late'] ?? 0);
        $earlyExitStatusId    = (int) ($statuses['early_exit'] ?? 0);
        $unregisteredStatusId = (int) ($statuses['unregistered'] ?? 0);

        if (!$absentStatusId || !$lateStatusId || !$earlyExitStatusId || !$unregisteredStatusId) {
            return [
                'error' => true,
                'code' => 500,
                'message' => 'Faltan estados base en attendance_statuses (codes: absent/late/early_exit/unregistered). Ejecuta el seeder.'
            ];
        }

        $realClass = RealClass::with('scheduleSession')->findOrFail($attendance->real_class_id);

        $statusId = (int) $data['attendance_status_id'];
        $start = Carbon::parse($realClass->start_hour);
        $lateFrom = $start->copy()->addMinutes(16);

        $attendanceData = [
            'attendance_status_id' => $statusId,
        ];

        // early_exit: solo requiere observaciones
        if ($statusId === $earlyExitStatusId) {
            if (empty($data['observations'])) {
                return ['error' => true, 'code' => 422, 'message' => 'Las observaciones son obligatorias para salida anticipada'];
            }

            if (is_null($attendance->entry_hour)) {
                return ['error' => true, 'code' => 422, 'message' => 'No se puede marcar salida anticipada sin una hora de entrada previa'];
            }

            $attendanceData['observations'] = $data['observations'];

            $attendanceData['absent_hours'] = $this->calculateAbsentHours([
                'real_class_id' => $attendance->real_class_id,
                'attendance_status_id' => $statusId,
                'entry_hour' => $attendance->entry_hour,
            ]);

            $attendance->update($attendanceData);

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

        if ($statusId === $absentStatusId || $statusId === $unregisteredStatusId) {
            $attendanceData['entry_hour'] = null;
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
        } else {
            $attendanceData['entry_hour'] = now()->format('H:i:s');
        }

        if (array_key_exists('observations', $data)) {
            $attendanceData['observations'] = $data['observations'];
        }

        $attendanceData['absent_hours'] = $this->calculateAbsentHours([
            'real_class_id' => $attendance->real_class_id,
            'attendance_status_id' => $statusId,
            'entry_hour' => $attendanceData['entry_hour'],
        ]);

        $attendance->update($attendanceData);

        if ($attendance->fresh()->attendance_status_id === $absentStatusId) {
            event(new ResourceChanged(
                'updated',
                Attendance::class,
                $attendance->id,
                Auth::id(),
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


    private function calculateAbsentHours($data)
    {
        $absentHours = 0;
        $realClass = RealClass::with('scheduleSession')->find($data['real_class_id']);

        $maxHours = (int) ceil($realClass->scheduleSession->durationSession);

        $statuses = $this->getStatusIds();
        $absentStatusId = (int) ($statuses['absent'] ?? 0);
        $lateStatusId = (int) ($statuses['late'] ?? 0);

        switch ((int) $data['attendance_status_id']) {
            case $absentStatusId:
                $absentHours = $maxHours;
                break;

            case $lateStatusId:
                $entryHour  = Carbon::parse($data['entry_hour']);
                $startClass = Carbon::parse($realClass->start_hour);

                if ($entryHour->lte($startClass)) {
                    $absentHours = 0;
                    break;
                }

                $minutesLate = $startClass->diffInMinutes($entryHour, true);
                $absentHours = min((int) ceil($minutesLate / 60), $maxHours);
                break;
        }

        return $absentHours;
    }

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

    private function summaryByRealClassId(int $realClassId)
    {
        $rows = Attendance::query()
            ->selectRaw('attendance_statuses.code as code, COUNT(*) as total')
            ->join('attendance_statuses', 'attendance_statuses.id', '=', 'attendances.attendance_status_id')
            ->where('attendances.real_class_id', $realClassId)
            ->groupBy('attendance_statuses.code')
            ->get();

        $countsByCode = $rows->pluck('total', 'code')->toArray();

        foreach (['present', 'absent', 'excused_absence', 'late', 'early_exit', 'unregistered'] as $c) {
            if (!array_key_exists($c, $countsByCode)) $countsByCode[$c] = 0;
        }

        return [
            'total' => array_sum($countsByCode),
            'counts_by_code' => $countsByCode,
        ];
    }

    public function scanCheckIn($data)
    {

        $documentNumber = $data['document_number'];

        $apprentice = Apprentice::query()
            ->where('document_number', $documentNumber)
            ->where('status_id', 1)
            ->first();

        if (!$apprentice) {
            return ['error' => true, 'code' => 404, 'message' => 'Aprendiz no encontrado o inactivo'];
        }

        $now = now();

        $realClass = $this->findScannableRealClassForFicha((int) $apprentice->ficha_id, $now);
        if (!$realClass) {
            return ['error' => true, 'code' => 404, 'message' => 'No hay clase real disponible para registrar en este momento'];
        }

        $statusIds = AttendanceStatus::whereIn('code', ['present', 'late'])
            ->pluck('id', 'code')
            ->toArray();

        $presentId = (int) ($statusIds['present'] ?? 0);
        $lateId    = (int) ($statusIds['late'] ?? 0);

        if (!$presentId || !$lateId) {
            return ['error' => true, 'code' => 500, 'message' => 'Faltan estados base (present/late). Ejecuta el seeder.'];
        }

        $start = Carbon::parse($realClass->start_hour);
        $lateFrom = $start->copy()->addMinutes(16);

        $isLate = $now->gte($lateFrom);
        $statusId = $isLate ? $lateId : $presentId;

        $attendance = Attendance::query()
            ->where('real_class_id', $realClass->id)
            ->where('apprentice_id', $apprentice->id)
            ->first();

        if (!$attendance) {
            return ['error' => true, 'code' => 404, 'message' => 'No existe asistencia base para esta clase real'];
        }

        if (!is_null($attendance->entry_hour)) {
            return ['error' => true, 'code' => 422, 'message' => 'Este aprendiz ya registró entrada'];
        }

        $entryHour = $now->format('H:i:s');

        $absentHours = $this->calculateAbsentHours([
            'real_class_id' => $realClass->id,
            'attendance_status_id' => $statusId,
            'entry_hour' => $entryHour,
        ]);

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

    private function findScannableRealClassForFicha(int $fichaId, Carbon $now)
    {
        $ficha = Ficha::query()
            ->with([
                'currentFichaTerm.schedule.scheduleSessions.realClasses' => function ($q) use ($now) {
                    $q->whereDate('execution_date', $now->toDateString());
                },
            ])
            ->find($fichaId);

        if (!$ficha?->currentFichaTerm?->schedule) {
            return null;
        }

        $realClasses = $ficha->currentFichaTerm->schedule
            ->scheduleSessions
            ->flatMap(fn($ss) => $ss->realClasses);

        return $realClasses
            ->sortBy(fn($rc) => $rc->start_hour)
            ->first(function ($rc) use ($now) {
                $start = Carbon::parse($rc->start_hour);
                $end   = Carbon::parse($rc->end_hour);

                $openFrom  = $start->copy()->subMinutes(30);
                $blockFrom = $end->copy()->subHour();

                return $now->between($openFrom, $blockFrom, true);
            });
    }
}
