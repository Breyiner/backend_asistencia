<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    protected $fillable = [
        'instructor_id',
        'schedule_id',
        'shift_id',
        'classroom_id',
        'day_id',
        'start_time',
        'end_time',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function day()
    {
        return $this->belongsTo(Day::class);
    }

    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'schedule_session_id');
    }
}
