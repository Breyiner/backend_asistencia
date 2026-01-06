<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = [
        'name',
    ];

    public function fichaTerm()
    {
        return $this->hasMany(FichaTerm::class, 'term_id');
    }
}
