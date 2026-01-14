<?php

namespace App\Services\Term;

use App\Events\ResourceChanged;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;

class TermService
{
    public static function getAll()
  {
    $terms = Term::all();

    if (count($terms) == 0) {
      return [
        "error" => false,
        "code" => 200,
        "message" => "No hay trimestres registrados",
        "data" => $terms,
      ];
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Trimestres obtenidos con éxito",
      "data" => $terms,
    ];
  }

  public function getById($id)
  {
    $term = Term::find($id);

    if (!$term) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este trimestre no existe",
      ];
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Trimestre obtenido con éxito",
      "data" => $term,
    ];
  }

  public function create(array $data)
  {
    $term = Term::create([
      'name' => $data['name'],
    ]);

    event(new ResourceChanged(
      'crear',
      Term::class,
      $term->id,
      Auth::id(),
      'Trimestre',
    ));

    return [
      "error" => false,
      "code" => 201,
      "message" => "Trimestre creado con éxito",
    ];
  }

  public function update(array $data, $id)
  {
    $term = Term::find($id);

    if (!$term) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este trimestre no existe",
      ];
    }

    $termData = [];

    if(array_key_exists('name', $data)) {
      $termData['name'] = $data['name'];
    }

    if(!empty($termData)) {
        $term->update($termData);

        event(new ResourceChanged(
          'actualizar',
          Term::class,
          $term->id,
          Auth::id(),
          'Trimestre',
        ));
    }

    return [
      "error" => false,
      "code" => 200,
      "message" => "Trimestre actualizado con éxito",
    ];
  }

  public function delete($id)
  {
    $term = Term::find($id);

    if (!$term) {
      return [
        "error" => true,
        "code" => 404,
        "message" => "Este trimestre no existe",
      ];
    }

    $term->delete();

    event(new ResourceChanged(
      'eliminar',
      Term::class,
      $term->id,
      Auth::id(),
      'Trimestre',
    ));

    return [
      "error" => false,
      "code" => 200,
      "message" => "Trimestre eliminado con éxito",
    ];
  }
}
