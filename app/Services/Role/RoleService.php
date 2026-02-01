<?php

namespace App\Services\Role;

use App\Events\ResourceChanged;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleService
{
  public static function getAll()
  {
    $roles = Role::all();

    if (count($roles) == 0) {
      return [
        "error" => false,
        "code" => 200,
        "message" => "No hay roles registrados",
        "data" => $roles,
      ];
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Roles obtenidos con éxito",
      "data" => $roles,
    ];
  }

  public function getSelectable()
  {
    $excludedCodes = ['PENDIENTE', 'APRENDIZ'];

    $roles = Role::select(['id', 'name', 'code'])
      ->whereNotIn('code', $excludedCodes)
      ->orderBy('name')
      ->get();

    return [
      "error" => false,
      "code" => 200,
      "message" => "Roles seleccionables obtenidos con éxito",
      "data" => $roles,
    ];
  }


  public function getRole($id)
  {
    $role = Role::find($id);

    if (!$role) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este rol no existe",
      ];
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Rol obtenido con éxito",
      "data" => $role,
    ];
  }

  public function createRole(array $data)
  {
    $role = Role::create([
      'name' => $data['name'],
      'code' => $data['code'], // NUEVO
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

  public function updateRole(array $data, $id)
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
      $roleData['description'] = $data['description'];
    }

    if (!empty($roleData)) {
      $role->update($roleData);

      event(new ResourceChanged(
        'actualizar',
        Role::class,
        $role->id,
        Auth::id(),
        'Rol'
      ));
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Rol actualizado con éxito",
      "data" => $role->fresh(),
    ];
  }

  public function deleteRole($id)
  {
    $role = Role::find($id);

    if (!$role) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este rol no existe",
      ];
    }

    if ($role->users->count() > 0) {
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
}
