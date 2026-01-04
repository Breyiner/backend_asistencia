<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualificationLevel extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }
}
