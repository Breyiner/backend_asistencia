<?php

namespace App\Services\Role;

use App\Events\ResourceChanged;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleService
{
  public function getAll($perPage = 10)
  {
    $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

    $query = Role::query()
      ->select(['id', 'name', 'code', 'description', 'guard_name', 'created_at', 'updated_at'])
      ->whereNotIn('code', $excludedCodes)
      ->withCount('users')
      ->orderBy('name', 'asc');

    if (request()->filled('role_name')) {
      $query->where('name', 'like', '%' . request('role_name') . '%');
    }

    if (request()->filled('role_code')) {
      $query->where('code', 'like', '%' . request('role_code') . '%');
    }

    $roles = $query->paginate($perPage);

    $items = $roles->getCollection()->map(function ($role) {
      return [
        'id' => $role->id,
        'name' => $role->name,
        'code' => $role->code,
        'description' => $role->description,
        'users_count' => (int) ($role->users_count ?? 0),
        'guard_name' => $role->guard_name,
        'created_at' => $role->created_at?->toDateString(),
        'updated_at' => $role->updated_at?->toDateString(),
      ];
    });

    // catálogo global de permisos (para modal)
    $allPermissions = Permission::query()
      ->select(['id', 'name', 'display_name', 'group', 'guard_name'])
      ->where('guard_name', 'web')
      ->orderBy('group')
      ->orderBy('display_name')
      ->get()
      ->map(function ($p) {
        return [
          'id' => $p->id,
          'name' => $p->name,
          'display_name' => $p->display_name,
          'group' => $p->group,
          'guard_name' => $p->guard_name,
        ];
      });

    if ($items->isEmpty()) {
      return [
        "error" => false,
        "code" => 200,
        "message" => "No hay roles registrados",
        "data" => $items,
        "paginate" => [
          "current_page" => $roles->currentPage(),
          "per_page" => $roles->perPage(),
          "total" => $roles->total(),
          "last_page" => $roles->lastPage(),
          "from" => $roles->firstItem(),
          "to" => $roles->lastItem(),
        ],
        "summary" => [
          "permissions" => $allPermissions,
        ],
      ];
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Roles obtenidos con éxito",
      "data" => $items,
      "paginate" => [
        "current_page" => $roles->currentPage(),
        "per_page" => $roles->perPage(),
        "total" => $roles->total(),
        "last_page" => $roles->lastPage(),
        "from" => $roles->firstItem(),
        "to" => $roles->lastItem(),
      ],
      "summary" => [
        "permissions" => $allPermissions,
      ],
    ];
  }

  public function getAllForSelect()
  {
    $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

    $query = Role::query()
      ->select(['id', 'name', 'code'])
      ->whereNotIn('code', $excludedCodes)
      ->orderBy('name', 'asc');

    if (request()->filled('role_name')) {
      $query->where('name', 'like', '%' . request('role_name') . '%');
    }

    if (request()->filled('role_code')) {
      $query->where('code', 'like', '%' . request('role_code') . '%');
    }

    $roles = $query->get();

    return [
      "error" => false,
      "code" => 200,
      "message" => "Roles seleccionables obtenidos con éxito",
      "data" => $roles,
    ];
  }

  public function getById($id)
  {
    $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

    $role = Role::query()
      ->select(['id', 'name', 'code', 'description', 'guard_name', 'created_at', 'updated_at'])
      ->whereNotIn('code', $excludedCodes)
      ->withCount('users')
      ->with(['permissions:id,name,display_name,group,guard_name'])
      ->find($id);

    if (!$role) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este rol no existe",
      ];
    }

    $item = [
      'id' => $role->id,
      'name' => $role->name,
      'code' => $role->code,
      'description' => $role->description,
      'guard_name' => $role->guard_name,
      'users_count' => (int) ($role->users_count ?? 0),
      'created_at' => $role->created_at?->toDateString(),
      'updated_at' => $role->updated_at?->toDateString(),
      'permissions' => $role->permissions->map(function ($p) {
        return [
          'id' => $p->id,
          'name' => $p->name,
          'display_name' => $p->display_name,
          'group' => $p->group,
          'guard_name' => $p->guard_name,
        ];
      })->values(),
    ];

    return [
      "error" => false,
      "code" => 200,
      "message" => "Rol obtenido con éxito",
      "data" => $item,
    ];
  }

  public function create(array $data)
  {
    $role = Role::create([
      'name' => $data['name'],
      'code' => $data['code'],
      'description' => $data['description'] ?? null,
      'guard_name' => 'web',
    ]);

    event(new ResourceChanged(
      'crear',
      Role::class,
      $role->id,
      Auth::id(),
      'Rol'
    ));

    return [
      "error" => false,
      "code" => 201,
      "message" => "Rol creado con éxito",
      "data" => $role,
    ];
  }

  public function update(array $data, $id)
  {
    $role = Role::find($id);

    if (!$role) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este rol no existe",
      ];
    }

    $roleData = [];

    if (array_key_exists('name', $data)) {
      $roleData['name'] = $data['name'];
    }
    if (array_key_exists('description', $data)) {
      $roleData['description'] = $data['description'] ?? null;
    }

    if (empty($roleData)) {
      return [
        "error" => false,
        "code" => 200,
        "message" => "No hay datos para actualizar",
        "data" => $role,
      ];
    }

    $role->update($roleData);

    event(new ResourceChanged(
      'actualizar',
      Role::class,
      $role->id,
      Auth::id(),
      'Rol'
    ));

    return [
      "error" => false,
      "code" => 200,
      "message" => "Rol actualizado con éxito",
      "data" => $role->fresh(),
    ];
  }

  public function delete($id)
  {
    $role = Role::find($id);

    if (!$role) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este rol no existe",
      ];
    }

    // No hidrates $role->users; solo valida existencia en DB
    if ($role->users()->exists()) {
      return [
        "error" => true,
        "code" => 400,
        "message" => "No se puede eliminar un rol asignado a usuarios",
      ];
    }

    $role->delete();

    event(new ResourceChanged(
      'eliminar',
      Role::class,
      $id,
      Auth::id(),
      'Rol'
    ));

    return [
      "error" => false,
      "code" => 200,
      "message" => "Rol eliminado con éxito",
    ];
  }

  public function syncPermissions($roleId, array $permissionIds)
  {
    $role = Role::find($roleId);

    if (!$role) {
      return ["error" => true, "code" => 404, "message" => "Este rol no existe"];
    }

    $permissions = Permission::query()
      ->where('guard_name', $role->guard_name)
      ->whereIn('id', $permissionIds)
      ->get();

    $role->syncPermissions($permissions);

    return [
      "error" => false,
      "code" => 200,
      "message" => "Permisos del rol actualizados con éxito",
      "data" => $role->load('permissions:id,name,display_name,group'),
    ];
  }
}
