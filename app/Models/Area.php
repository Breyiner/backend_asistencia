<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo **Área** de formación SENA.
 *
 * Relaciona programas de formación y usuarios por roles/áreas.
 */
class Area extends Model
{
    use HasFactory;

    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * **Relación HAS_MANY:** Programas de formación del área.
     */
    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }

    /**
     * **Relación MANY-TO-MANY:** Usuarios asignados al área.
     * 
     * Tabla pivot: `area_user` con timestamps.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'area_user')->withTimestamps();
    }

    /**
     * **MÉTODO SCOPE** usuarios por códigos de rol.
     * 
     * @param array|string $codes Códigos rol: ['INSTRUCTOR', 'COORDINADOR']
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersByRoleCode($codes)
    {
        $codes = is_array($codes) ? $codes : [$codes];

        return $this->users()->whereHas('roles', function ($q) use ($codes) {
            $q->whereIn('code', $codes);
        });
    }

    /**
     * **VERIFICA** si área tiene usuario específico.
     */
    public function hasUser($userId): bool
    {
        return $this->users()->where('users.id', $userId)->exists();
    }
}
