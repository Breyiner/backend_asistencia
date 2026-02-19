<?php

namespace App\Models;

/**
 * Perfil específico **aprendiz** hereda de UserProfile.
 *
 * **Extiende** con fecha nacimiento y relación inversa a Apprentice.
 * Usa `extraFillable` para extender campos mass assignment.
 */
class ApprenticeProfile extends UserProfile
{
    /**
     * Campos **ADICIONALES** para mass assignment.
     * 
     * Se **MERGEAN** con fillable de UserProfile padre.
     */
    protected $extraFillable = ['birth_date'];

    /**
     * **CASTS** tipos de datos.
     * 
     * `date:Y-m-d` → Formato legible en frontend
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
        ];
    }

    /**
     * **SOBRESCRIBE** getFillable() para incluir extraFillable.
     */
    public function getFillable()
    {
        return array_merge(parent::getFillable(), $this->extraFillable);
    }

    /**
     * **Relación BELONGS_TO inversa:** Apprentice dueño del perfil.
     */
    public function apprentice()
    {
        return $this->belongsTo(Apprentice::class, 'user_id');
    }

    /**
     * **ACCESOR** edad calculada.
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date?->age ?? 0;
    }

    /**
     * **SCOPES** por rango edad.
     */
    public function scopeAdults($query)
    {
        return $query->ofAge(18);
    }
}
