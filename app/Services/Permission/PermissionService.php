<?php

namespace App\Services\Permission;

use App\Models\Permission;
use Illuminate\Support\Collection;

class PermissionService
{
    public function select()
    {
        $query = Permission::query()
            ->select(['id', 'name', 'display_name', 'group', 'guard_name'])
            ->where('guard_name', 'web')
            ->orderBy('group')
            ->orderBy('display_name');

        if (request()->filled('q')) {
            $q = request('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('display_name', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('group', 'like', "%{$q}%");
            });
        }

        $items = $query->get();

        // agrupado para modal
        $grouped = $items->groupBy('group')->map(function (Collection $perms) {
            return $perms->values();
        });

        return [
            "error" => false,
            "code" => 200,
            "message" => "Permisos obtenidos con éxito",
            "data" => $grouped,
        ];
    }
}