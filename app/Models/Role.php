<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Modelo **Rol** extendido de Spatie Permission.
 *
 * Agrega **code** único y métodos utilitarios para búsqueda por código.
 */
class Role extends SpatieRole
{
    /**
     * Campos permitidos para **mass assignment**.
     * 
     * **Extra:** code, description
     */
    protected $fillable = [
        'name',        // "Instructor", "Coordinador", "Aprendiz"
        'code',        // "INSTRUCTOR", "COORDINADOR", "APPRENTICE"
        'guard_name',  // "web", "api"
        'description'  // Descripción legible del rol
    ];

    /**
     * **ESTÁTICO** Obtiene IDs por array de códigos.
     * 
     * @param array $codes Códigos únicos: ['INSTRUCTOR', 'COORDINADOR']
     * @return array ['INSTRUCTOR' => 1, 'COORDINADOR' => 2]
     * @throws \RuntimeException Si código no existe
     */
    public static function idsByCodes(array $codes)
    {
        $codes = array_values(array_unique(array_filter($codes)));

        if ($codes === []) {
            return [];
        }

        $map = static::query()
            ->whereIn('code', $codes)
            ->pluck('id', 'code')
            ->toArray();

        $missing = array_values(array_diff($codes, array_keys($map)));

        if ($missing !== []) {
            throw new \RuntimeException(
                "Roles no existen para code(s): " . implode(', ', $missing)
            );
        }

        return array_map('intval', $map);
    }

    /**
     * **ESTÁTICO** Obtiene ID por código único.
     * 
     * @param string $code Código rol: 'INSTRUCTOR'
     * @return int ID del rol
     */
    public static function idByCode(string $code)
    {
        return static::idsByCodes([$code])[$code];
    }
}
