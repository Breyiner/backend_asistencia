<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    protected $fillable = [
        'gestor_id',
        'ficha_number',
        'start_date',
        'end_date',
        'training_program_id',
        'status_id',
    ];

    public function gestor()
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function status()
    {
        return $this->belongsTo(FichaStatus::class, 'status_id');
    }

    public function fichaTerm()
    {
        return $this->hasMany(FichaTerm::class, 'term_id');
    }
}
