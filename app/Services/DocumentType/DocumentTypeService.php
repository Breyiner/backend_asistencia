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
     * Retorna una lista paginada de tipos de documento con filtros opcionales.
     *
     * @param  int  $perPage  Cantidad de registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        $query = DocumentType::query()
            ->select(['id', 'name', 'acronym', 'created_at', 'updated_at']);

        if (request()->filled('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        if (request()->filled('acronym')) {
            $query->where('acronym', 'like', '%' . request('acronym') . '%');
        }

        $documentTypes = $query->orderBy('name', 'asc')->paginate($perPage);

        $items = $documentTypes->getCollection()->map(function ($documentType) {
            return [
                'id'         => $documentType->id,
                'name'       => $documentType->name,
                'acronym'    => $documentType->acronym,
                'created_at' => $documentType->created_at?->toDateString(),
                'updated_at' => $documentType->updated_at?->toDateString(),
            ];
        });

        $paginate = [
            'current_page' => $documentTypes->currentPage(),
            'per_page'     => $documentTypes->perPage(),
            'total'        => $documentTypes->total(),
            'last_page'    => $documentTypes->lastPage(),
            'from'         => $documentTypes->firstItem(),
            'to'           => $documentTypes->lastItem(),
        ];

        if ($items->isEmpty()) {
            return [
                'error'    => false,
                'code'     => 200,
                'message'  => 'No hay tipos de documento registrados',
                'data'     => $items,
                'paginate' => $paginate,
            ];
        }

        return [
            'error'    => false,
            'code'     => 200,
            'message'  => 'Tipos de documento obtenidos exitosamente',
            'data'     => $items,
            'paginate' => $paginate,
        ];
    }

    /**
     * Retorna todos los tipos de documento en formato simplificado para selects/dropdowns.
     *
     * @return array
     */
    public function getAllForSelect()
    {
        $query = DocumentType::query()
            ->select(['id', 'name', 'acronym'])
            ->orderBy('name', 'asc');

        if (request()->filled('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        $documentTypes = $query->get();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Tipos de documento obtenidos exitosamente',
            'data'    => $documentTypes,
        ];
    }

    /**
     * Retorna un tipo de documento por su ID.
     *
     * @param  mixed  $id
     * @return array
     */
    public function getById($id)
    {
        $documentType = DocumentType::query()
            ->select(['id', 'name', 'acronym', 'created_at', 'updated_at'])
            ->find($id);

        if (!$documentType) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Tipo de documento no encontrado',
            ];
        }

        $item = [
            'id'         => $documentType->id,
            'name'       => $documentType->name,
            'acronym'    => $documentType->acronym,
            'created_at' => $documentType->created_at?->toDateString(),
            'updated_at' => $documentType->updated_at?->toDateString(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Tipo de documento obtenido exitosamente',
            'data'    => $item,
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
        $documentType = DocumentType::create([
            'name'    => $data['name'],
            'acronym' => $data['acronym'],
        ]);

        event(new ResourceChanged(
            'crear',
            DocumentType::class,
            $documentType->id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Tipo de documento creado exitosamente',
            'data'    => $documentType,
        ];
    }

    /**
     * Actualiza un tipo de documento existente.
     *
     * Solo actualiza los campos presentes en $data.
     *
     * @param  array  $data
     * @param  mixed  $id
     * @return array
     */
    public function update(array $data, $id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Tipo de documento no encontrado',
            ];
        }

        $documentTypeData = [];

        if (array_key_exists('name', $data)) {
            $documentTypeData['name'] = $data['name'];
        }

        if (array_key_exists('acronym', $data)) {
            $documentTypeData['acronym'] = $data['acronym'];
        }

        if (empty($documentTypeData)) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay datos para actualizar',
                'data'    => $documentType,
            ];
        }

        $documentType->update($documentTypeData);

        event(new ResourceChanged(
            'actualizar',
            DocumentType::class,
            $documentType->id,
            Auth::id(),
            'Tipo de documento'
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Tipo de documento actualizado exitosamente',
            'data'    => $documentType->fresh(),
        ];
    }

    /**
     * Elimina un tipo de documento por su ID.
     *
     * Verifica que no haya usuarios usando este tipo antes de eliminar,
     * protegiendo la integridad referencial. Retorna 409 si está en uso.
     *
     * @param  mixed  $id
     * @return array
     */
    public function delete($id)
    {
        $documentType = DocumentType::find($id);

        if (!$documentType) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Tipo de documento no encontrado',
            ];
        }

        // exists() es más eficiente que count() porque detiene la consulta
        // al encontrar el primer usuario relacionado.
        if ($documentType->users()->exists()) {
            return [
                'error'   => true,
                'code'    => 409,
                'message' => 'No se puede eliminar el tipo de documento porque tiene registros relacionados',
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
            'error'   => false,
            'code'    => 200,
            'message' => 'Tipo de documento eliminado exitosamente',
        ];
    }
}