<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'area_user')->withTimestamps();
    }

    public function usersByRoleCode($codes)
    {
        $codes = is_array($codes) ? $codes : [$codes];

        return $this->users()->whereHas('roles', function ($q) use ($codes) {
            $q->whereIn('code', $codes);
        });
    }

    public function hasUser($userId): bool
    {
        return $this->users()->where('users.id', $userId)->exists();
    }
}