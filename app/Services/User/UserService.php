<?php

namespace App\Services\User;

use App\Events\ResourceChanged;
use App\Events\UserCreated;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio de lógica de negocio para la gestión de usuarios del sistema.
 *
 * Gestiona el ciclo de vida completo de un usuario: creación con contraseña
 * generada automáticamente, actualización de perfil/roles/áreas, cambio de
 * contraseña y eliminación. Los usuarios con type IS NOT NULL son aprendices
 * y se excluyen de todos los métodos de gestión con whereNull('type').
 *
 * Lógica de áreas: solo los roles definidos en ROLES_WITH_AREAS pueden tener
 * áreas asignadas. Si el usuario pierde esos roles, sus áreas se desvinculan
 * automáticamente para mantener la consistencia del modelo.
 */
class UserService
{
    /**
     * Roles que pueden tener áreas geográficas/académicas asignadas.
     * Los demás roles (APRENDIZ, SCANNER, PENDIENTE) no gestionan áreas.
     */
    private const ROLES_WITH_AREAS = ['ADMIN', 'COORDINADOR', 'GESTOR_FICHAS', 'INSTRUCTOR'];

    /**
     * Retorna una lista paginada de usuarios con filtros opcionales.
     *
     * Excluye al usuario autenticado (no tiene sentido que se vea a sí mismo en el listado).
     * Excluye aprendices con whereNull('type') (tienen su propio módulo de gestión).
     * Soporta filtros por email, documento, nombre, apellido, teléfono, rol, área y estado.
     *
     * @param  int  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        $query = User::select([
            'id',
            'email',
            'document_number',
            'status_id',
        ])
            // Excluye aprendices: tienen type IS NOT NULL (ej: 'apprentice').
            ->whereNull('type')
            // Excluye al usuario autenticado del listado de gestión.
            ->where('id', '!=', Auth::id())
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name,code',
                'areas:id,name',
            ]);

        // Filtros opcionales sobre campos directos del usuario.
        if (request()->filled('email')) {
            $query->where('email', 'like', '%' . request('email') . '%');
        }

        if (request()->filled('document_number')) {
            $query->where('document_number', 'like', '%' . request('document_number') . '%');
        }

        if (request()->filled('document_type_id')) {
            $query->where('document_type_id', request('document_type_id'));
        }

        if (request()->filled('status_name')) {
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', request('status_name') . '%');
            });
        }

        // Filtros sobre relaciones (profile, roles, areas).
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

        if (request()->filled('role_name')) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'like', '%' . request('role_name') . '%');
            });
        }

        if (request()->filled('area_id')) {
            // areas.id: califica la columna para evitar ambigüedad en el JOIN de la tabla pivote.
            $query->whereHas('areas', function ($q) {
                $q->where('areas.id', request('area_id'));
            });
        }

        $users = $query->paginate($perPage);

        $items = $users->getCollection()->map(function ($user) {
            return [
                'id'              => $user->id,
                'first_name'      => $user->profile?->first_name ?? '',
                'last_name'       => $user->profile?->last_name  ?? '',
                'email'           => $user->email,
                // pluck() devuelve Collection; toArray() no es necesario aquí
                // pero se mantiene consistente con getById() para el frontend.
                'roles'           => $user->roles->pluck('name'),
                'areas'           => $user->areas->pluck('name')->toArray(),
                'status'          => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number,
            ];
        });

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Usuarios obtenidos con éxito',
            'data'    => $items,
            'paginate' => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'last_page'    => $users->lastPage(),
                'from'         => $users->firstItem(),
                'to'           => $users->lastItem(),
            ],
        ];
    }

    /**
     * Retorna todos los usuarios con un rol específico como lista plana.
     *
     * Uso: selects de instructor, coordinador, gestor en formularios de asignación.
     * full_name se construye aquí (a diferencia de getAll()) porque estos selects
     * suelen mostrar el nombre completo directamente.
     *
     * @param  int  $roleId  ID del rol por el que filtrar.
     * @return array
     */
    public function getAllByRoleId(int $roleId)
    {
        $users = User::select([
            'id',
            'email',
            'document_number',
            'status_id',
        ])
            ->whereNull('type')
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name,code',
            ])
            ->whereHas('roles', function ($q) use ($roleId) {
                $q->where('id', $roleId);
            })
            ->get();

        $items = $users->map(function ($user) {
            $first = $user->profile?->first_name ?? '';
            $last  = $user->profile?->last_name  ?? '';

            return [
                'id'              => $user->id,
                'first_name'      => $first,
                'last_name'       => $last,
                // trim() evita espacio doble si algún nombre es vacío.
                'full_name'       => trim("$first $last"),
                'email'           => $user->email,
                'roles'           => $user->roles->pluck('name'),
                'status'          => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number,
            ];
        });

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Usuarios obtenidos con éxito',
            'data'    => $items,
        ];
    }

    /**
     * Retorna todos los usuarios asignados a un área específica como lista plana.
     *
     * Uso: selects de usuarios disponibles en un área para asignación de fichas o programas.
     * La columna se califica como 'areas.id' para evitar ambigüedad con el JOIN de la pivote.
     *
     * @param  int  $areaId  ID del área por la que filtrar.
     * @return array
     */
    public function getAllByAreaId(int $areaId)
    {
        $users = User::select([
            'id',
            'email',
            'document_number',
            'status_id',
        ])
            ->whereNull('type')
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name,code',
            ])
            ->whereHas('areas', function ($q) use ($areaId) {
                // areas.id calificado para evitar ambigüedad en la tabla pivote area_user.
                $q->where('areas.id', $areaId);
            })
            ->get();

        $items = $users->map(function ($user) {
            $first = $user->profile?->first_name ?? '';
            $last  = $user->profile?->last_name  ?? '';

            return [
                'id'              => $user->id,
                'first_name'      => $first,
                'last_name'       => $last,
                'full_name'       => trim("$first $last"),
                'email'           => $user->email,
                'roles'           => $user->roles->pluck('name'),
                'status'          => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number,
            ];
        });

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Usuarios del área obtenidos con éxito',
            'data'    => $items,
        ];
    }

    /**
     * Retorna el detalle completo de un usuario por su ID.
     *
     * Incluye más campos que getAll(): role_ids, area_ids, document_type,
     * teléfono y timestamps. Los arrays de IDs permiten al frontend pre-seleccionar
     * valores en los selects de edición sin una segunda petición.
     *
     * @param  mixed  $id  ID del usuario.
     * @return array
     */
    public function getById($id)
    {
        $user = User::whereNull('type')
            ->with([
                'profile',
                'status',
                'roles:id,name,code',
                'areas:id,name',
                // documentType no está en with() del original: se accede directamente en el map.
                // Si da N+1, agregar 'documentType:id,name' aquí.
            ])
            ->find($id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        $item = [
            'id'                   => $user->id,
            'first_name'           => $user->profile?->first_name ?? '',
            'last_name'            => $user->profile?->last_name  ?? '',
            'email'                => $user->email,
            // toArray(): convierte Collection a array para serialización JSON consistente.
            'roles'                => $user->roles->pluck('name')->toArray(),
            'role_ids'             => $user->roles->pluck('id')->toArray(),
            'areas'                => $user->areas->pluck('name')->toArray(),
            'area_ids'             => $user->areas->pluck('id')->toArray(),
            'status_id'            => $user->status?->id,
            'status'               => $user->status?->name ?? 'Sin estado',
            'document_number'      => $user->document_number,
            'document_type_id'     => $user->document_type_id,
            'document_type_name'   => $user->documentType?->name ?? '',
            'telephone_number'     => $user->profile?->telephone_number ?? '',
            'created_at'           => $user->created_at?->toDateString(),
            'updated_at'           => $user->updated_at?->toDateString(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Usuario obtenido con éxito',
            'data'    => $item,
        ];
    }

    /**
     * Crea un nuevo usuario con contraseña generada automáticamente.
     *
     * Flujo dentro de la transacción:
     *   1. Crea el User con contraseña hasheada.
     *   2. Crea el UserProfile asociado.
     *   3. Sincroniza roles (o asigna 'Pendiente' si no se envían).
     *   4. Sincroniza áreas solo si el rol del usuario lo permite.
     *
     * Post-transacción (fuera del try para no revertir por fallo de notificación):
     *   - Dispara UserCreated con la contraseña en texto plano para el email de bienvenida.
     *   - Envía verificación de email.
     *   - Dispara ResourceChanged para auditoría.
     *
     * @param  array  $data  Datos validados del request.
     * @return array
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            // Genera contraseña segura de 12 caracteres para enviarla al usuario por email.
            $password = Str::password(12);

            $user = User::create([
                'email'            => $data['email'],
                'password'         => Hash::make($password),
                'document_number'  => $data['document_number'],
                'document_type_id' => $data['document_type_id'],
            ]);

            $profile = UserProfile::create([
                'user_id'          => $user->id,
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'],
                'telephone_number' => $data['telephone_number'],
            ]);

            // Si no se envían roles, asigna 'Pendiente' como estado de espera de asignación.
            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($user, $data['roles']);
            } else {
                $user->assignRole('Pendiente');
            }

            // Sincroniza áreas solo si el rol del usuario lo permite.
            // loadMissing evita recargar roles si ya están en memoria.
            if (array_key_exists('area_ids', $data) && !empty($data['area_ids'])) {
                $user->loadMissing('roles:id,code');
                if ($this->userCanHaveAreas($user)) {
                    $user->areas()->sync($data['area_ids']);
                } else {
                    // El rol no permite áreas: asegura que no queden áreas residuales.
                    $user->areas()->detach();
                }
            }

            DB::commit();

            // Post-transacción: notificaciones fuera del try para no revertir por fallo de email.
            event(new UserCreated([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                // Contraseña en texto plano: solo se envía por email, nunca se persiste sin hashear.
                'password'   => $password,
                'created_at' => $user->created_at,
            ]));

            $user->sendEmailVerificationNotification();

            event(new ResourceChanged(
                'crear',
                User::class,
                $user->id,
                Auth::id(),
                'Usuario',
            ));

            return [
                'error'   => false,
                'code'    => 201,
                'message' => 'Usuario creado con éxito',
                'data'    => compact('user', 'password', 'profile'),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'error'   => true,
                'code'    => 500,
                'message' => "Ocurrió un error al crear el usuario: {$e->getMessage()}",
                'data'    => [],
            ];
        }
    }

    /**
     * Actualiza los datos de un usuario existente.
     *
     * Actualiza en una sola transacción: datos del User, datos del UserProfile,
     * roles y áreas. Si el email cambia, resetea email_verified_at para forzar
     * reverificación. Si los roles cambian y el nuevo rol no permite áreas, las desvincula.
     *
     * @param  array   $data  Campos a actualizar.
     * @param  string  $id    ID del usuario.
     * @return array
     */
    public function update(array $data, string $id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);

            if (!$user) {
                return [
                    'error'   => true,
                    'code'    => 404,
                    'message' => 'Este usuario no existe',
                    'data'    => [],
                ];
            }

            // Campos del modelo User (tabla users).
            $userData = [];

            if (array_key_exists('document_number', $data))  $userData['document_number']  = $data['document_number'];
            if (array_key_exists('document_type_id', $data)) $userData['document_type_id'] = $data['document_type_id'];
            if (array_key_exists('status_id', $data))        $userData['status_id']        = $data['status_id'];

            // Si el email cambia, resetea verificación: el usuario debe reverificar el nuevo email.
            if (array_key_exists('email', $data) && $data['email'] !== $user->email) {
                $userData['email']              = $data['email'];
                $userData['email_verified_at']  = null;
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            // Campos del UserProfile (tabla user_profiles).
            $profileData = [];

            if (array_key_exists('first_name', $data))       $profileData['first_name']       = $data['first_name'];
            if (array_key_exists('last_name', $data))        $profileData['last_name']         = $data['last_name'];
            if (array_key_exists('telephone_number', $data)) $profileData['telephone_number']  = $data['telephone_number'];

            if (!empty($profileData)) {
                // update() sobre la relación: actualiza directamente sin recargar el modelo.
                $user->profile()->update($profileData);
            }

            // Sincroniza roles; si no se envían, asigna 'Pendiente'.
            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($user, $data['roles']);
            } else {
                $user->assignRole('Pendiente');
            }

            // Sincroniza áreas: si el nuevo rol no lo permite, desvincula las existentes.
            if (array_key_exists('area_ids', $data)) {
                $user->loadMissing('roles:id,code');
                if ($this->userCanHaveAreas($user)) {
                    $user->areas()->sync($data['area_ids']);
                } else {
                    $user->areas()->detach();
                }
            }

            DB::commit();

            event(new ResourceChanged(
                'actualizar',
                User::class,
                $user->id,
                Auth::id(),
                'Usuario',
            ));

            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'Usuario actualizado con éxito',
                'data'    => [
                    'user'    => $user->fresh(),
                    'profile' => $user->profile,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'error'   => true,
                'code'    => 500,
                'message' => "Error al actualizar: {$e->getMessage()}",
                'data'    => [],
            ];
        }
    }

    /**
     * Actualiza los roles de un usuario y limpia sus áreas si el nuevo rol no las permite.
     *
     * Operación atómica: roles y áreas se actualizan juntos para mantener consistencia.
     * Se dispara evento de auditoría independientemente de si las áreas cambiaron.
     *
     * @param  array  $roleIds  IDs de los roles a asignar (reemplaza los existentes).
     * @param  mixed  $id       ID del usuario.
     * @return array
     */
    public function updateRoles(array $roleIds, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        // syncRoles(): reemplaza todos los roles del usuario con los enviados.
        $this->syncUserRoles($user, $roleIds);

        // Si el nuevo conjunto de roles no permite áreas, las desvincula automáticamente.
        $user->loadMissing('roles:id,code');
        if (!$this->userCanHaveAreas($user)) {
            $user->areas()->detach();
        }

        event(new ResourceChanged(
            'actualizar',
            User::class,
            $user->id,
            Auth::id(),
            'Usuario',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Roles del usuario actualizados con éxito',
            'data'    => [],
        ];
    }

    /**
     * Actualiza las áreas de un usuario.
     *
     * Verifica que el rol del usuario permita tener áreas antes de sincronizar.
     * Si el rol no lo permite, retorna 403 en lugar de silenciar la operación.
     *
     * @param  array  $areaIds  IDs de las áreas a asignar (reemplaza las existentes).
     * @param  mixed  $id       ID del usuario.
     * @return array
     */
    public function updateAreas(array $areaIds, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        $user->loadMissing('roles:id,code');

        // Verifica antes de sincronizar: evita asignar áreas a roles que no las usan.
        if (!$this->userCanHaveAreas($user)) {
            return [
                'error'   => true,
                'code'    => 403,
                'message' => 'Este usuario no puede tener áreas asignadas.',
                'data'    => [],
            ];
        }

        // sync(): reemplaza todas las áreas del usuario con las enviadas.
        $user->areas()->sync($areaIds);

        event(new ResourceChanged(
            'actualizar',
            User::class,
            $user->id,
            Auth::id(),
            'Usuario',
        ));

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Áreas del usuario actualizadas con éxito',
            'data'    => [],
        ];
    }

    /**
     * Sincroniza los roles de un usuario reemplazando la asignación completa.
     *
     * Método privado: no se expone como endpoint, solo lo usan create(), update() y updateRoles().
     * syncRoles() de Spatie es equivalente a detachAll + attachMany.
     *
     * @param  User   $user     Instancia del usuario a actualizar.
     * @param  array  $roleIds  IDs de los roles a asignar.
     */
    private function syncUserRoles(User $user, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        // syncRoles(): reemplaza TODOS los roles actuales del usuario (Spatie Permission).
        $user->syncRoles($roles);
    }

    /**
     * Determina si un usuario puede tener áreas asignadas según su rol activo.
     *
     * Compara los códigos de los roles del usuario contra ROLES_WITH_AREAS.
     * array_intersect(): retorna los elementos comunes entre ambos arrays.
     * Si el resultado no está vacío, el usuario tiene al menos un rol con áreas.
     *
     * @param  User  $user  Usuario con roles ya cargados en memoria.
     * @return bool
     */
    private function userCanHaveAreas(User $user): bool
    {
        $codes = $user->roles->pluck('code')->filter()->values()->toArray();
        // filter(): elimina valores null/vacíos antes de comparar.
        return !empty(array_intersect(self::ROLES_WITH_AREAS, $codes));
    }

    /**
     * Actualiza la contraseña de un usuario.
     *
     * Si se envía current_password, verifica que coincida con la almacenada
     * antes de actualizar (cambio autenticado). Si no se envía, aplica el
     * cambio directamente (reset por admin).
     *
     * @param  array  $data  Debe contener 'password'; 'current_password' es opcional.
     * @param  mixed  $id    ID del usuario.
     * @return array
     */
    public function updatePassword(array $data, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        // Verifica la contraseña actual solo si fue enviada en el request (flujo usuario).
        // Si no se envía (flujo admin), permite el cambio directo sin verificación.
        if (!empty($data['current_password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return [
                    'error'   => true,
                    'code'    => 401,
                    'message' => 'Contraseña incorrecta',
                    'data'    => [],
                ];
            }
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Contraseña actualizada con éxito',
            'data'    => [],
        ];
    }

    /**
     * Elimina un usuario por su ID (soft delete si el modelo lo implementa).
     *
     * No verifica roles ni relaciones antes de eliminar: se asume que
     * la restricción de integridad se maneja a nivel de política o middleware.
     *
     * @param  mixed  $id  ID del usuario.
     * @return array
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        $user->delete();

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Usuario eliminado con éxito',
            'data'    => [],
        ];
    }
}
