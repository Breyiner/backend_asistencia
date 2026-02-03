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

    public function noClassReason()
    {
        return $this->belongsTo(NoClassReason::class, 'reason_id');
    }
}
