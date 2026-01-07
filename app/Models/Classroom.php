<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function scheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class);
    }
}
