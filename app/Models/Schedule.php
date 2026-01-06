<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [

        'description',
        'ficha_term_id',

    ];

    public function fichaTerm()
    {
        return $this->belongsTo(FichaTerm::class, 'ficha_term_id')->with(['ficha', 'term']);
    }
}
