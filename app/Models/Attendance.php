<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Asistencia** de clase real.
 *
 * Registra asistencia individual de aprendices por clase real.
 */
class Attendance extends Model
{
    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'real_class_id',
        'apprentice_id',
        'attendance_status_id',
        'entry_hour',
        'absent_hours',
        'observations',
    ];

    /**
     * **Relación BELONGS_TO:** Clase real donde se tomó asistencia.
     */
    public function realClass()
    {
        return $this->belongsTo(RealClass::class, 'real_class_id');
    }

    /**
     * **Relación BELONGS_TO:** Aprendiz que asistió/no asistió.
     */
    public function apprentice()
    {
        return $this->belongsTo(User::class, 'apprentice_id');
    }

    /**
     * **Relación BELONGS_TO:** Estado de asistencia (Presente, Tardanza, etc).
     */
    public function attendanceStatus()
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id');
    }
}
