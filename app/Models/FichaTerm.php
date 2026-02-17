<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaTerm extends Model
{
    protected $fillable = [

        'term_id',
        'ficha_id',
        'phase_id',
        'start_date',
        'end_date',
        'is_current'

    ];

    protected $casts = [

        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'

    ];


    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'ficha_term_id');
    }
}
