<?php

namespace App\Services\DocumentType;

use App\Events\ResourceChanged;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Auth;

class DocumentTypeService
{
    public static function getAll()
    {
        $documentType = DocumentType::all();

        if ($documentType->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay tipos de documento registrados",
                "data" => $documentType,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Tipos de documento obtenidos exitosamente",
            "data" => $documentType,
        ];
    }

    public function getById($id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Tipo de documento no encontrado",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Tipo de documento obtenido exitosamente",
            "data" => $documentType,
        ];
    }

    public function create(array $data)
    {
        $documentType = DocumentType::create($data);

        event(new ResourceChanged(
            'crear',
            DocumentType::class,
            $documentType->id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            "error" => false,
            "code" => 201,
            "message" => "Tipo de documento creado exitosamente",
            "data" => $documentType,
        ];
    }

    public function update(array $data, $id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Tipo de documento no encontrado",
            ];
        }

        $documentType->update($data);

        event(new ResourceChanged(
            'actualizar',
            DocumentType::class,
            $documentType->id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Tipo de documento actualizado exitosamente",
            "data" => $documentType->fresh(),
        ];
    }

    public function partialUpdate(array $data, $id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Tipo de documento no encontrado",
            ];
        }

        $documentType->update($data);

        event(new ResourceChanged(
            'actualizar',
            DocumentType::class,
            $documentType->id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Tipo de documento actualizado parcialmente exitosamente",
            "data" => $documentType->fresh(),
        ];
    }

    public function delete($id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Tipo de documento no encontrado",
            ];
        }

        if ($documentType->users()->exists()) {
            return [
                "error" => true,
                "code" => 409,
                "message" => "No se puede eliminar el Tipo de documento porque tiene registros relacionados",
            ];
        }

        $documentType->delete();

        event(new ResourceChanged(
            'eliminar',
            DocumentType::class,
            $id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Tipo de documento eliminado exitosamente",
        ];
    }
}
