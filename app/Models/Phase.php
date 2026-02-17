<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function fichaTerm()
    {
        return $this->hasMany(FichaTerm::class, 'term_id');
    }
}
