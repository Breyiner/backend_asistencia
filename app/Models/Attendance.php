<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'real_class_id',
        'apprentice_id',
        'attendance_status_id',
        'entry_hour',
        'absent_hours',
        'observations',
    ];

    public function realClass()
    {
        return $this->belongsTo(RealClass::class, 'real_class_id');
    }

    public function apprentice()
    {
        return $this->belongsTo(User::class, 'apprentice_id');
    }

    public function attendanceStatus()
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id');
    }
}
