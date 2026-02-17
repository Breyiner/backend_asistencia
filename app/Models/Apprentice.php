<?php

namespace App\Models;

use Parental\HasParent;

class Apprentice extends User
{
    use HasParent;

    protected $fillable = [
        'ficha_id',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }

    public function profile()
    {
        return $this->hasOne(ApprenticeProfile::class, 'user_id');
    }
}
