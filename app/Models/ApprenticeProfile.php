<?php

namespace App\Models;


class ApprenticeProfile extends UserProfile
{
    protected $extraFillable = ['birth_date'];

    public function getFillable()
    {
        return array_merge(parent::getFillable(), $this->extraFillable);
    }

    public function apprentice()
    {
        return $this->belongsTo(Apprentice::class, 'user_id');
    }
}
