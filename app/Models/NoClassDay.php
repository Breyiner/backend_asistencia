<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoClassDay extends Model
{
    protected $fillable = [
        'ficha_id',
        'date',
        'reason_id',
        'observations'
    ];

    public function reason()
    {
        return $this->belongsTo(NoClassReason::class, 'reason_id');
    }

    public function ficha()
    {
        return $this->belongsTo(Ficha::class,'ficha_id');
    }
}
