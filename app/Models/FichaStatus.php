<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaStatus extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'status_id');
    }
}
