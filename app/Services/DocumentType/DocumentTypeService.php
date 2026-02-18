<?php

namespace App\Services\DocumentType;

use App\Events\ResourceChanged;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de lógica de negocio para la gestión de tipos de documento.
 *
 * Los tipos de documento (cédula, tarjeta de identidad, etc.) son un catálogo base
 * usado por usuarios y aprendices. Incluye protección de integridad referencial
 * en el delete para evitar eliminar tipos que ya estén en uso.
 */
class DocumentTypeService
{
    /**
     * Retorna todos los tipos de documento.
     *
     * Método estático porque no requiere estado de instancia; se puede llamar
     * directamente desde el controlador sin instanciar el servicio.
     * Diferencia en el mensaje si el catálogo está vacío o tiene registros.
     *
     * @return array
     */
    public static function getAll()
    {
        $documentType = DocumentType::all();

        // Retorna un mensaje diferenciado si el catálogo está vacío,
        // sin lanzar error ya que es un estado válido del sistema.
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

    /**
     * Retorna un tipo de documento por su ID.
     *
     * @param  mixed  $id  ID del tipo de documento.
     * @return array
     */
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

    /**
     * Crea un nuevo tipo de documento.
     *
     * @param  array  $data  Datos validados desde el request.
     * @return array
     */
    public function create(array $data)
    {
        $documentType = DocumentType::create($data);

        // Dispara el evento después de la creación para auditoría o notificaciones.
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

    /**
     * Actualiza completamente un tipo de documento (PUT).
     *
     * Reemplaza todos los campos del registro con los valores enviados.
     * A diferencia de partialUpdate(), aquí se espera que $data contenga
     * todos los campos requeridos del modelo.
     *
     * @param  array  $data  Todos los campos del tipo de documento.
     * @param  mixed  $id    ID del tipo de documento.
     * @return array
     */
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

        // Actualiza todos los campos enviados sin construcción dinámica,
        // porque en un PUT se asume que llegan todos los campos.
        $documentType->update($data);

        // Dispara el evento después de confirmar la actualización.
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
            // fresh() recarga el modelo desde BD para devolver los datos ya persistidos.
            "data" => $documentType->fresh(),
        ];
    }

    /**
     * Actualiza parcialmente un tipo de documento (PATCH).
     *
     * Solo actualiza los campos presentes en $data, sin afectar los demás.
     * La validación de qué campos son opcionales la maneja el FormRequest.
     *
     * @param  array  $data  Campos a actualizar (pueden ser uno o varios).
     * @param  mixed  $id    ID del tipo de documento.
     * @return array
     */
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

        // Eloquent solo actualiza los campos presentes en $data,
        // dejando los demás campos del modelo sin cambios.
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

    /**
     * Elimina un tipo de documento por su ID.
     *
     * Verifica que no haya usuarios usando este tipo de documento antes de eliminar,
     * protegiendo la integridad referencial. Retorna 409 si está en uso.
     *
     * @param  mixed  $id  ID del tipo de documento.
     * @return array
     */
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

        // exists() es más eficiente que count() porque detiene la consulta al encontrar
        // el primer usuario relacionado, sin traer ni contar todos los resultados.
        // 409 Conflict indica que el recurso no puede eliminarse por dependencias activas.
        if ($documentType->users()->exists()) {
            return [
                "error" => true,
                "code" => 409,
                "message" => "No se puede eliminar el Tipo de documento porque tiene registros relacionados",
            ];
        }

        $documentType->delete();

        // Se pasa $id y no $documentType->id porque el modelo ya no existe en BD tras el delete().
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