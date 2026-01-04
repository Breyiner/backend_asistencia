<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration',
        'qualification_level_id',
        'area_id',
    ];

    public function qualificationLevel()
    {
        return $this->belongsTo(QualificationLevel::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
