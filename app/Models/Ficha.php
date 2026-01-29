<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    protected $fillable = [
        'gestor_id',
        'shift_id',
        'ficha_number',
        'start_date',
        'end_date',
        'training_program_id',
        'status_id',
    ];

    public function casts()
    {
        return [

            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',

        ];
    }

    public function gestor()
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function apprentices()
    {
        return $this->hasMany(User::class, 'ficha_id');
    }

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function status()
    {
        return $this->belongsTo(FichaStatus::class, 'status_id');
    }

    public function fichaTerms()
    {
        return $this->hasMany(FichaTerm::class, 'ficha_id');
    }

    public function currentFichaTerm()
    {
        return $this->hasOne(FichaTerm::class, 'ficha_id')->where('is_current', true);
    }
}
