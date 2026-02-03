<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoClassReason extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function noClassDays()
    {
        return $this->hasMany(NoClassDay::class, 'reason_id');
    }
}