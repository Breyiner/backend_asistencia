<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name','code','guard_name','description'];

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

    public static function idByCode(string $code)
    {
        return static::idsByCodes([$code])[$code];
    }
}