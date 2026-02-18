<?php

namespace App\Models;

use Parental\HasParent;

/**
 * Modelo **Aprendiz** hereda de User.
 * 
 * Representa estudiantes inscritos en fichas SENA.
 * Extiende User con relación específica a Ficha.
 */
class Apprentice extends User
{
    use HasParent;

    /**
     * Campos permitidos para **mass assignment**.
     */
    protected $fillable = [
        'ficha_id',
    ];

    /**
     * **Relación BELONGS_TO:** Ficha del aprendiz.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }

    /**
     * **Relación HAS_ONE:** Perfil específico del aprendiz.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(ApprenticeProfile::class, 'user_id');
    }

    /**
     * **ACCESOR** para nombre completo.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * **SCOPES** útiles.
     */
    public function scopeByFicha($query, int $fichaId)
    {
        return $query->where('ficha_id', $fichaId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'Activo'));
    }

    /**
     * **Relaciones adicionales** heredadas de User.
     * 
     * - roles()     ← Pivot roles_users
     * - areas()     ← Pivot areas_users  
     * - status()    ← user_statuses
     */
}
