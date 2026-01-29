<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
    ];

    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'time_slot_id');
    }
}
