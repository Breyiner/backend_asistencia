<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    protected $fillable = [
        'name',
        'day_number'
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];
}
