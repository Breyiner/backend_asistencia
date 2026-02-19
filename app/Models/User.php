<?php

namespace App\Models;

use App\Notifications\CustomResetPassword;
use App\Notifications\CustomVerifyEmail;
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
     * Guard por defecto para Spatie Permission.
     */
    protected $guard_name = 'web';

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

    /**
     * **Herencia polimórfica** con Parental (Aprendiz).
     */
    protected $childTypes = [
        'apprentice' => Apprentice::class,
    ];

    /**
     * **Relación BELONGS_TO:** Tipo de documento.
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * **Relación BELONGS_TO:** Estado del usuario.
     */
    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id');
    }

    /**
     * **Relación HAS_ONE:** Perfil del usuario.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * **Relación HAS_MANY:** Fichas gestionadas por este usuario.
     */
    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'gestor_id');
    }

    /**
     * **Relación HAS_MANY:** Sesiones donde es instructor.
     */
    public function instructorScheduleSessions()
    {
        return $this->hasMany(ScheduleSession::class, 'instructor_id');
    }

    /**
     * **Relación HAS_MANY:** Programas coordinados.
     */
    public function coordinatedTrainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class, 'coordinator_id');
    }

    /**
     * **Relación HAS_MANY:** Clases reales dictadas.
     */
    public function instructorRealClasses()
    {
        return $this->hasMany(RealClass::class, 'instructor_id');
    }

    /**
     * **Relación MANY-TO-MANY:** Notificaciones recibidas.
     * 
     * Pivot con read_at y role_code.
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->withPivot(['read_at', 'role_code'])
            ->withTimestamps();
    }

    /**
     * **ACCESOR** datos auth para frontend.
     * 
     * Incluye roles con permisos.
     */
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

    /**
     * **Relación MANY-TO-MANY:** Áreas asignadas al usuario.
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_user')->withTimestamps();
    }

    /**
     * **VERIFICA** acceso a área específica.
     */
    public function hasAccessToArea($areaId): bool
    {
        return $this->areas()->where('areas.id', $areaId)->exists();
    }

    /**
     * **ACCESOR** IDs de áreas asignadas.
     */
    public function areaIds(): array
    {
        return $this->areas()->pluck('areas.id')->toArray();
    }

    /**
     * **SOBREESCRIBE** notificación verificación email personalizada.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    /**
     * **SOBREESCRIBE** notificación reset password personalizada.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword($token, $this->email));
    }
}
