<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealClass extends Model
{
    protected $fillable = [
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
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class, 'schedule_session_id');
    } 
    
    public function attendances()
    {
        return $this->hasMany(Attendance::class,'real_class_id');
    }
}
