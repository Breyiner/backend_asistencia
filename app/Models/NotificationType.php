<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    protected $fillable = [
        'name',
        'key',
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
