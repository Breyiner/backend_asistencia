<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    protected $fillable = [
        'instructor_id',
        'schedule_id',
        'time_slot_id',
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

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
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

    public function getDurationSessionAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInHours($end, false);
    }
}
