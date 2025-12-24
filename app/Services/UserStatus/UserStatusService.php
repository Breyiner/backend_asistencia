<?php

namespace App\Services\UserStatus;

use App\Models\UserStatus;

use Illuminate\Support\Arr;

class UserStatusService
{

  public static function getAll()
  {

    $statuses = UserStatus::all();

    if (count($statuses) == 0)
      return [
        "error" => false,
        "code" => 200,
        "message" => "No hay estados registrados",
        "data" => $statuses
      ];


    return [
      "error" => false,
      "code" => 200,
      "message" => "Estados obtenidos con éxito",
      "data" => $statuses
    ];
  }

  public function getStatus($id)
  {

    $status = UserStatus::find($id);

    if (!$status)
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este estado no existe",
      ];

    return [
      "error" => false,
      "code" => 200,
      "message" => "Estado obtenido con éxito",
      "data" => $status
    ];
  }

  public function createStatus(array $data)
  {

    $status = UserStatus::create([
      'name' => $data['name'],
      'description' => $data['description'],
    ]);

    return [
      'error' => false,
      'code' => 201,
      'message' => 'Estado creado con éxito',
    ];
  }

  public function updateStatus(array $data, $id)
  {

    $status = UserStatus::find($id);

    if (!$status)
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este estado no existe",
      ];

    $status->update(Arr::only($data, ['name', 'description']));

    return [
      "error" => false,
      "code" => 200,
      "message" => "Estado actualizado con éxito",
    ];
  }

  public function partialUpdateStatus(array $entryData, $id)
  {

    $status = UserStatus::find($id);

    if (!$status)
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este estado no existe",
      ];

    $status->update($entryData);

    return [
      "error" => false,
      "code" => 200,
      "message" => "Estado actualizado con éxito",
    ];
  }

  public function deleteStatus($id)
  {

    $status = UserStatus::find($id);

    if (!$status)
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este estado no existe",
      ];

    if ($status->users()->exists()) {
      return [
        "error" => true,
        "code" => 409,
        "message" => "No se puede eliminar el estado porque tiene usuarios relacionados",
      ];
    }

    $status->delete();

    return [
      "error" => false,
      "code" => 200,
      "message" => "Estado eliminado con éxito",
    ];
  }
}