<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\RealClass;
use Carbon\Carbon;

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
        $attendances = Attendance::where('real_class_id', $realClassId)
            ->with('attendanceStatus')
            ->get();

        if ($attendances->isEmpty()) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'No hay asistencias para esta clase real',
            ];
        }

        return [
            'error' => false,
            'code' => 200,
            'message' => 'Asistencias de clase obtenidas correctamente',
            'data' => $attendances
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

    public function update($data, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return ['error' => true, 'code' => 404, 'message' => 'Asistencia no encontrada'];
        }

        if (!empty($data['checkout']) && $data['checkout'] === true) {

            if (is_null($attendance->entry_hour)) {
                return ['error' => true, 'code' => 422, 'message' => 'No se puede registrar salida sin una hora de entrada previa'];
            }

            $earlyExitStatusId = 5;

            if (!array_key_exists('attendance_status_id', $data) || empty($data['attendance_status_id'])) {

                if (!is_null($attendance->exit_hour)) {
                    return ['error' => true, 'code' => 422, 'message' => 'La salida ya fue registrada'];
                }

                $attendanceData = [
                    'exit_hour' => now()->format('H:i:s'),
                ];

                if (array_key_exists('observations', $data)) {
                    $attendanceData['observations'] = $data['observations'];
                }

                $attendance->update($attendanceData);

                return ['error' => false, 'code' => 200, 'message' => 'Salida registrada correctamente'];
            }

            $statusId = (int) $data['attendance_status_id'];

            if ($statusId !== $earlyExitStatusId) {
                return ['error' => true, 'code' => 422, 'message' => 'attendance_status_id inválido para registrar salida'];
            }

            $exitHour = Carbon::createFromFormat('H:i', $data['exit_hour'])->format('H:i:s');

            $entry = Carbon::parse($attendance->entry_hour);
            if (Carbon::parse($exitHour)->lt($entry)) {
                return ['error' => true, 'code' => 422, 'message' => 'La hora de salida no puede ser menor que la hora de entrada'];
            }

            $attendanceData = [
                'attendance_status_id' => $statusId,
                'exit_hour' => $exitHour,
            ];

            if (array_key_exists('observations', $data)) {
                $attendanceData['observations'] = $data['observations'];
            }

            $attendance->update($attendanceData);

            return ['error' => false, 'code' => 200, 'message' => 'Salida registrada correctamente'];
        }

        if (!array_key_exists('attendance_status_id', $data)) {
            return ['error' => true, 'code' => 422, 'message' => 'attendance_status_id es requerido'];
        }

        $realClass = RealClass::with('scheduleSession')->findOrFail($attendance->real_class_id);

        $statusId = (int) $data['attendance_status_id'];
        $start = Carbon::parse($realClass->start_hour);

        $lateFrom = $start->copy()->addMinutes(16);

        $attendanceData = [
            'attendance_status_id' => $statusId,
        ];

        if ($statusId === 2) {
            $attendanceData['entry_hour'] = null;
        } elseif ($statusId === 4) {

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

        $calcData = [
            'real_class_id' => $attendance->real_class_id,
            'attendance_status_id' => $statusId,
            'entry_hour' => $attendanceData['entry_hour'],
        ];

        $attendanceData['absent_hours'] = $this->calculateAbsentHours($calcData);

        $attendance->update($attendanceData);

        return ['error' => false, 'code' => 200, 'message' => 'Asistencia actualizada correctamente'];
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

    private function calculateAbsentHours($data)
    {
        $absentHours = 0;
        $realClass = RealClass::with('scheduleSession')->find($data['real_class_id']);

        $maxHours = (int) ceil($realClass->scheduleSession->durationSession);

        switch ((int) $data['attendance_status_id']) {
            case 2:
                $absentHours = $maxHours;
                break;

            case 4:
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
}
