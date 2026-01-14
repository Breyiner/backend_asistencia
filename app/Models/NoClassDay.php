<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoClassDay extends Model
{
    protected $fillable = [
        'ficha_id',
        'date',
        'reason',
    ];
}
