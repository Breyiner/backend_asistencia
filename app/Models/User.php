<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Parental\HasChildren;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles, HasChildren;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'email',
        'password',
        'document_type_id',
        'status_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d'
        ];
    }

    protected $childTypes = [
        'apprentice' => Apprentice::class,
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id');
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'gestor_id');
    }

    public function instructorScheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class, 'instructor_id');
    }

    public function instructorRealClasses()
    {
        return $this->hasMany(RealClass::class, 'instructor_id');
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function getAuthDataAttribute()
    {
        return [
            'id' => $this->id,
            'name' => $this->profile ? $this->profile->first_name . ' ' . $this->profile->last_name : '',
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d'),
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'code' => $role->code,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            })->toArray()
        ];
    }
}
