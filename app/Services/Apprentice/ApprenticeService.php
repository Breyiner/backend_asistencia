<?php

namespace App\Services\Apprentice;

use App\Events\ResourceChanged;
use App\Models\Apprentice;
use App\Models\Role;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lógica de negocio para la gestión de aprendices.
 *
 * Centraliza las operaciones CRUD sobre aprendices, aplicando
 * filtros de acceso según el rol del usuario autenticado.
 */
class ApprenticeService
{

    /**
     * Retorna una lista paginada de aprendices con filtros opcionales.
     *
     * El resultado se filtra automáticamente según el rol activo del usuario:
     * COORDINADOR, GESTOR_FICHAS o INSTRUCTOR solo ven sus aprendices asociados.
     *
     * @param  int  $perPage  Cantidad de registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        // Obtiene el ID del usuario autenticado y su rol activo desde los atributos del request.
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Selecciona solo los campos necesarios para el listado y precarga relaciones.
        $query = Apprentice::select([
            'id',
            'email',
            'document_number',
            'status_id',
            'ficha_id',
        ])
            ->with([
                'profile:id,user_id,first_name,last_name,telephone_number',
                'status:id,name',
                'ficha:id,ficha_number,gestor_id,training_program_id',
                'ficha.trainingProgram:id,name,coordinator_id',
            ]);

        // Restringe resultados según el rol: cada rol solo accede a sus aprendices vinculados.
        if ($roleCode === 'COORDINADOR') {
            // Solo aprendices de fichas cuyo programa tiene a este usuario como coordinador.
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            // Solo aprendices de fichas donde este usuario es gestor.
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            // Solo aprendices que el instructor tiene en sus sesiones de horario activo.
            $query->whereHas('ficha.currentFichaTerm.schedule.scheduleSessions', function ($q) use ($userId) {
                $q->where('instructor_id', $userId);
            });
        }

        // Filtros opcionales por campos del aprendiz enviados en el request.
        if (request()->filled('email')) {
            $query->where('email', 'like', '%' . request('email') . '%');
        }

        if (request()->filled('document_number')) {
            $query->where('document_number', 'like', '%' . request('document_number') . '%');
        }

        if (request()->filled('status_name')) {
            // Filtra por nombre de estado usando relación.
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', request('status_name') . '%');
            });
        }

        if (request()->filled('document_type_id')) {
            $query->where('document_type_id', request('document_type_id'));
        }

        // Filtros sobre la relación profile (nombre y apellido).
        if (request()->filled('first_name')) {
            $query->whereHas('profile', function ($q) {
                $q->where('first_name', 'like', '%' . request('first_name') . '%');
            });
        }

        if (request()->filled('last_name')) {
            $query->whereHas('profile', function ($q) {
                $q->where('last_name', 'like', '%' . request('last_name') . '%');
            });
        }

        if (request()->filled('telephone_number')) {
            $query->whereHas('profile', function ($q) {
                $q->where('telephone_number', 'like', '%' . request('telephone_number') . '%');
            });
        }

        if (request()->filled('ficha_number')) {
            // Filtra por número de ficha usando relación.
            $query->whereHas('ficha', function ($q) {
                $q->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
            });
        }

        // Ejecuta la paginación con los filtros aplicados.
        $apprentices = $query->paginate($perPage);

        // Mapea cada aprendiz a un array plano con los campos necesarios para la respuesta.
        $items = $apprentices->getCollection()->map(function ($apprentice) {
            return [
                'id' => $apprentice->id,
                'first_name' => $apprentice->profile?->first_name ?? '',
                'last_name' => $apprentice->profile?->last_name ?? '',
                'email' => $apprentice->email,
                'status' => $apprentice->status?->name ?? 'Sin estado',
                'document_number' => $apprentice->document_number,
                'telephone_number' => $apprentice->profile?->telephone_number ?? '',
                'ficha_number' => $apprentice->ficha?->ficha_number ?? '',
            ];
        });

        // Si no hay resultados, retorna mensaje informativo con paginación vacía.
        if ($items->isEmpty()) {
            return [
                'error' => false,
                'code' => 200,
                'message' => 'No hay aprendices registrados',
                'data' => [],
                'paginate' => [
                    'current_page' => $apprentices->currentPage(),
                    'per_page' => $apprentices->perPage(),
                    'total' => $apprentices->total(),
                    'last_page' => $apprentices->lastPage(),
                    'from' => $apprentices->firstItem(),
                    'to' => $apprentices->lastItem(),
                ],
            ];
        }

        // Retorna los aprendices encontrados junto con la metadata de paginación.
        return [
            'error' => false,
            'code' => 200,
            'message' => 'Aprendices obtenidos con éxito',
            'data' => $items,
            'paginate' => [
                'current_page' => $apprentices->currentPage(),
                'per_page' => $apprentices->perPage(),
                'total' => $apprentices->total(),
                'last_page' => $apprentices->lastPage(),
                'from' => $apprentices->firstItem(),
                'to' => $apprentices->lastItem(),
            ],
        ];
    }

    /**
     * Retorna el detalle completo de un aprendiz por su ID.
     *
     * Aplica las mismas restricciones de acceso por rol que el listado.
     *
     * @param  mixed  $id  ID del aprendiz.
     * @return array
     */
    public function getById($id)
    {
        // Obtiene el ID y rol del usuario autenticado.
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

        // Selecciona campos completos e incluye relaciones necesarias para el detalle.
        $query = Apprentice::select([
            'id',
            'email',
            'document_number',
            'document_type_id',
            'status_id',
            'ficha_id',
            'created_at',
            'updated_at',
        ])
            ->with([
                'profile:id,user_id,first_name,last_name,telephone_number,birth_date',
                'status:id,name',
                'documentType:id,name',
                'ficha:id,ficha_number,training_program_id,gestor_id',
                'ficha.trainingProgram:id,name,coordinator_id',
                'roles:id,name',
            ]);

        // Aplica restricciones de visibilidad según el rol activo.
        if ($roleCode === 'COORDINADOR') {
            $query->whereHas('ficha.trainingProgram', function ($q) use ($userId) {
                $q->where('coordinator_id', $userId);
            });
        } elseif ($roleCode === 'GESTOR_FICHAS') {
            $query->whereHas('ficha', function ($q) use ($userId) {
                $q->where('gestor_id', $userId);
            });
        } elseif ($roleCode === 'INSTRUCTOR') {
            $query->whereHas('ficha.currentFichaTerm.schedule.scheduleSessions', function ($q) use ($userId) {
                $q->where('instructor_id', $userId);
            });
        }

        // Busca el aprendiz; si no existe o no es visible para el rol, retorna 404.
        $apprentice = $query->find($id);

        if (!$apprentice) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este aprendiz no existe",
            ];
        }

        // Construye el array de respuesta con todos los datos del aprendiz.
        $items = [
            'id' => $apprentice->id,
            'first_name' => $apprentice->profile?->first_name ?? '',
            'last_name' => $apprentice->profile?->last_name ?? '',
            'email' => $apprentice->email,
            'roles' => $apprentice->roles->pluck('name')->toArray(),       // Nombres de los roles.
            'role_ids' => $apprentice->roles->pluck('id')->toArray(),      // IDs de los roles.
            'status_id' => $apprentice->status?->id,
            'status' => $apprentice->status?->name ?? 'Sin estado',
            'document_number' => $apprentice->document_number,
            'document_type_id' => $apprentice->document_type_id,
            'document_type_name' => $apprentice->documentType?->name ?? '',
            'telephone_number' => $apprentice->profile?->telephone_number ?? '',
            'birth_date' => $apprentice->profile?->birth_date?->toDateString() ?? '',
            'ficha_id' => $apprentice->ficha_id,
            'ficha_number' => $apprentice->ficha?->ficha_number ?? '',
            'training_program_id' => $apprentice->ficha?->trainingProgram?->id ?? null,
            'training_program' => $apprentice->ficha?->trainingProgram?->name ?? '',
            'created_at' => $apprentice->created_at?->toDateString(),
            'updated_at' => $apprentice->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Aprendiz obtenido con éxito",
            "data" => $items
        ];
    }

    /**
     * Crea un nuevo aprendiz con su perfil y rol asignado.
     *
     * Ejecuta la creación dentro de una transacción para garantizar consistencia.
     *
     * @param  array  $data  Datos del aprendiz validados desde el request.
     * @return array
     */
    public function create($data)
    {
        try {
            // Inicia la transacción: desde este punto, todas las operaciones a la base de datos
            // quedan en un estado pendiente. Si cualquiera falla, se pueden revertir todas juntas.
            // Esto garantiza que no quede un aprendiz sin perfil, o sin rol, por un error parcial.
            DB::beginTransaction();

            // Operación 1: Crea el registro principal del aprendiz.
            // Si esta línea falla, el catch hace rollback y no se inserta nada.
            $apprentice = Apprentice::create([
                'document_type_id' => $data['document_type_id'],
                'document_number' => $data['document_number'],
                'email' => $data['email'],
                'password' => null,      // Sin contraseña inicial; se establece después si aplica.
                'status_id' => 1,
                'ficha_id' => $data['ficha_id']
            ]);

            // Operación 2: Crea el perfil vinculado al aprendiz.
            // Depende del ID generado en la operación anterior. Si falla, el rollback
            // también deshace la creación del aprendiz, evitando registros huérfanos.
            $apprentice->profile()->create([
                'user_id' => $apprentice->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'telephone_number' => $data['telephone_number'],
                'birth_date' => $data['birth_date']
            ]);

            // Operación 3: Asigna el rol APRENDIZ en la tabla pivot.
            // syncWithoutDetaching agrega el rol sin eliminar otros que el usuario ya tenga.
            // Si esta operación falla, el rollback deshace también el aprendiz y el perfil.
            $apprenticeRoleId = Role::idByCode('APRENDIZ');
            $apprentice->roles()->syncWithoutDetaching([$apprenticeRoleId]);

            // Confirma todas las operaciones en la base de datos de forma atómica.
            // Solo llega aquí si las 3 operaciones anteriores fueron exitosas.
            DB::commit();

            // El evento se dispara DESPUÉS del commit, ya que el registro ya existe en BD.
            // Dispararlo antes podría causar que un listener lea datos aún no confirmados.
            event(new ResourceChanged(
                'crear',
                Apprentice::class,
                $apprentice->id,
                Auth::id(),
                'Aprendiz',
            ));

            return [
                'error' => false,
                'code' => 200,
                'message' => 'Aprendiz Creado con éxito',
                'data' => $apprentice
            ];

        } catch (Exception $e) {
            // Si cualquiera de las 3 operaciones lanzó una excepción, se revierten todos
            // los cambios pendientes, dejando la base de datos en el estado anterior.
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Ocurrió un error al crear el aprendiz {$e->getMessage()}",
            ];
        }
    }

    /**
     * Actualiza los datos de un aprendiz existente.
     *
     * Solo actualiza los campos presentes en $data, sin pisar campos no enviados.
     *
     * @param  array   $data  Campos a actualizar.
     * @param  string  $id    ID del aprendiz.
     * @return array
     */
    public function update(array $data, String $id)
    {
        try {
            // Inicia la transacción: el update toca dos tablas distintas (aprendiz y perfil).
            // Si la actualización del perfil falla después de haber actualizado al aprendiz,
            // el rollback revierte ambas operaciones y evita que queden datos inconsistentes.
            DB::beginTransaction();

            // Verifica que el aprendiz exista antes de intentar actualizarlo.
            $apprentice = Apprentice::find($id);

            if (!$apprentice)
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este aprendiz no existe",
                ];

            // Construye el array de campos del aprendiz solo con los valores enviados.
            $apprenticeData = [];

            if (array_key_exists('document_number', $data)) {
                $apprenticeData['document_number'] = $data['document_number'];
            }

            if (array_key_exists('document_type_id', $data)) {
                $apprenticeData['document_type_id'] = $data['document_type_id'];
            }

            if (array_key_exists('email', $data) && $data['email'] !== $apprentice->email) {
                // Si el email cambió, se actualiza y se invalida la verificación anterior.
                $apprenticeData['email'] = $data['email'];
                $apprenticeData['email_verified_at'] = null;
            }

            if (array_key_exists('status_id', $data)) {
                $apprenticeData['status_id'] = $data['status_id'];
            }

            if (array_key_exists('ficha_id', $data)) {
                $apprenticeData['ficha_id'] = $data['ficha_id'];
            }

            // Solo ejecuta el update si hay campos del aprendiz que cambiar.
            if (!empty($apprenticeData)) {
                $apprentice->update($apprenticeData);
            }

            // Construye el array de campos del perfil solo con los valores enviados.
            $profileData = [];
            if (array_key_exists('first_name', $data)) {
                $profileData['first_name'] = $data['first_name'];
            }
            if (array_key_exists('last_name', $data)) {
                $profileData['last_name'] = $data['last_name'];
            }
            if (array_key_exists('telephone_number', $data)) {
                $profileData['telephone_number'] = $data['telephone_number'];
            }
            if (array_key_exists('birth_date', $data)) {
                $profileData['birth_date'] = $data['birth_date'];
            }

            // Solo actualiza el perfil si hay campos del perfil que cambiar.
            if (!empty($profileData)) {
                $apprentice->profile()->update($profileData);
            }

            // Confirma ambas actualizaciones (aprendiz + perfil) de forma atómica.
            // Si llegó hasta aquí, las dos tablas se actualizaron correctamente.
            DB::commit();

            return [
                'error' => false,
                'code' => 200,
                'data' => [
                    'user' => $apprentice,
                    'profile' => $apprentice->profile
                ],
                'message' => 'Aprendiz actualizado con éxito',
            ];

        } catch (Exception $e) {
            // Revierte cualquier cambio parcial hecho en aprendiz o perfil antes del error.
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Error al actualizar: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Elimina un aprendiz por su ID (soft delete si está configurado en el modelo).
     *
     * @param  mixed  $id  ID del aprendiz.
     * @return array
     */
    public function destroy($id)
    {
        // Verifica que el aprendiz exista antes de eliminarlo.
        $apprentice = Apprentice::find($id);

        if (!$apprentice)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este aprendiz no existe",
            ];

        // Elimina el aprendiz (soft o hard delete según configuración del modelo).
        $apprentice->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Aprendiz eliminado con éxito",
        ];
    }
}