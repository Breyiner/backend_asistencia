<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function realClasses()
    {
        return $this->hasMany(RealClass::class, 'class_type_id');
    }
}
