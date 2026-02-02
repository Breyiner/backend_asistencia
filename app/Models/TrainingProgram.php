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
        'coordinator_id'
    ];

    public function qualificationLevel()
    {
        return $this->belongsTo(QualificationLevel::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function fichas()
    {
        return $this->hasMany(Ficha::class);
    }

    public function apprentices()
    {
        return $this->hasManyThrough(
            Apprentice::class,
            Ficha::class,
            'training_program_id',
            'ficha_id',
            'id',
            'id'
        );
    }
}
