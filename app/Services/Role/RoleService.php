<?php

namespace App\Services\Role;

use App\Events\ResourceChanged;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * Servicio de lógica de negocio para la gestión de roles del sistema.
 *
 * Usa el modelo Role de Spatie Permission, extendido con campos propios
 * (code, description). Los roles PENDIENTE, APRENDIZ y SCANNER son roles
 * internos del sistema y se excluyen de todos los listados públicos.
 *
 * Responsabilidades:
 * - Listar roles con paginación y catálogo de permisos embebido (para modal de asignación).
 * - CRUD de roles con eventos de auditoría.
 * - Sincronización de permisos por rol (syncPermissions de Spatie).
 */
class RoleService
{
    /**
     * Retorna una lista paginada de roles con el catálogo global de permisos embebido.
     *
     * El campo 'summary.permissions' se incluye en la misma respuesta para evitar
     * una segunda petición desde el frontend al abrir el modal de asignación de permisos.
     * Se filtra guard_name = 'web' en permisos para excluir guards de API u otros contextos.
     *
     * @param  int  $perPage  Registros por página (default: 10).
     * @return array
     */
    public function getAll($perPage = 10)
    {
        // Roles internos del sistema: no deben ser visibles ni asignables desde la UI.
        $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

        $query = Role::query()
            ->select(['id', 'name', 'code', 'description', 'guard_name', 'created_at', 'updated_at'])
            ->whereNotIn('code', $excludedCodes)
            // withCount('users'): cuenta usuarios con el rol sin cargar los modelos en memoria.
            ->withCount('users')
            ->orderBy('name', 'asc');

        // Filtros opcionales: búsqueda por nombre o code del rol.
        if (request()->filled('role_name')) {
            $query->where('name', 'like', '%' . request('role_name') . '%');
        }

        if (request()->filled('role_code')) {
            $query->where('code', 'like', '%' . request('role_code') . '%');
        }

        $roles = $query->paginate($perPage);

        // Transforma a array plano con tipos explícitos.
        $items = $roles->getCollection()->map(function ($role) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'code'        => $role->code,
                'description' => $role->description,
                'users_count' => (int) ($role->users_count ?? 0),
                'guard_name'  => $role->guard_name,
                'created_at'  => $role->created_at?->toDateString(),
                'updated_at'  => $role->updated_at?->toDateString(),
            ];
        });

        // Catálogo global de permisos para el modal de asignación:
        // se carga siempre (con o sin roles) para que el frontend no necesite una segunda petición.
        $allPermissions = Permission::query()
            ->select(['id', 'name', 'display_name', 'group', 'guard_name'])
            ->where('guard_name', 'web')
            ->orderBy('group')
            ->orderBy('display_name')
            ->get()
            ->map(function ($p) {
                return [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'display_name' => $p->display_name,
                    'group'        => $p->group,
                    'guard_name'   => $p->guard_name,
                ];
            });

        // Bloque de paginación reutilizado en ambas ramas (vacío y con datos).
        $paginate = [
            'current_page' => $roles->currentPage(),
            'per_page'     => $roles->perPage(),
            'total'        => $roles->total(),
            'last_page'    => $roles->lastPage(),
            'from'         => $roles->firstItem(),
            'to'           => $roles->lastItem(),
        ];

        if ($items->isEmpty()) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay roles registrados',
                'data'    => [],
                'paginate' => $paginate,
                'summary' => ['permissions' => $allPermissions],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Roles obtenidos con éxito',
            'data'    => $items,
            'paginate' => $paginate,
            'summary' => ['permissions' => $allPermissions],
        ];
    }

    /**
     * Retorna todos los roles visibles como lista plana para selects del frontend.
     *
     * A diferencia de getAll(), no pagina ni incluye permisos: el frontend solo
     * necesita id, name y code para poblar dropdowns (asignación de rol a usuario, etc.).
     *
     * @return array
     */
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
            'error'   => false,
            'code'    => 200,
            'message' => 'Roles seleccionables obtenidos con éxito',
            'data'    => $roles,
        ];
    }

    /**
     * Retorna el detalle de un rol por su ID, incluyendo sus permisos asignados.
     *
     * Excluye roles internos del sistema con whereNotIn() antes del find()
     * para que un intento de acceder a PENDIENTE/APRENDIZ/SCANNER devuelva 404.
     *
     * @param  mixed  $id  ID del rol.
     * @return array
     */
    public function getById($id)
    {
        $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

        // whereNotIn aplicado antes de find(): si el ID corresponde a un rol excluido, retorna null → 404.
        $role = Role::query()
            ->select(['id', 'name', 'code', 'description', 'guard_name', 'created_at', 'updated_at'])
            ->whereNotIn('code', $excludedCodes)
            ->withCount('users')
            // Incluye permisos del rol para mostrarlos en la vista de detalle/edición.
            ->with(['permissions:id,name,display_name,group,guard_name'])
            ->find($id);

        if (!$role) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este rol no existe',
                'data'    => [],
            ];
        }

        $item = [
            'id'          => $role->id,
            'name'        => $role->name,
            'code'        => $role->code,
            'description' => $role->description,
            'guard_name'  => $role->guard_name,
            'users_count' => (int) ($role->users_count ?? 0),
            'created_at'  => $role->created_at?->toDateString(),
            'updated_at'  => $role->updated_at?->toDateString(),
            // values() descarta claves numéricas del Collection para que el frontend reciba un array limpio.
            'permissions' => $role->permissions->map(function ($p) {
                return [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'display_name' => $p->display_name,
                    'group'        => $p->group,
                    'guard_name'   => $p->guard_name,
                ];
            })->values(),
        ];

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Rol obtenido con éxito',
            'data'    => $item,
        ];
    }

    /**
     * Crea un nuevo rol con guard_name fijo a 'web'.
     *
     * El campo 'code' es único e inmutable tras la creación (no se actualiza en update()).
     * guard_name se fuerza a 'web' en el servicio y no se expone al cliente.
     *
     * @param  array  $data  Datos validados (name, code requeridos; description opcional).
     * @return array
     */
    public function create(array $data)
    {
        $role = Role::create([
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
            // guard_name siempre 'web': los roles de API usan un guard diferente si aplica.
            'guard_name'  => 'web',
        ]);

        event(new ResourceChanged(
            'crear',
            Role::class,
            $role->id,
            Auth::id(),
            'Rol'
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Rol creado con éxito',
            'data'    => $role,
        ];
    }

    /**
     * Actualiza un rol existente.
     *
     * Solo permite actualizar 'name' y 'description': el campo 'code' es inmutable
     * porque se usa como identificador en middleware, políticas y lógica de negocio.
     * Si no hay campos válidos, retorna éxito con el rol sin modificar.
     *
     * @param  array  $data  Campos a actualizar (name, description).
     * @param  mixed  $id    ID del rol.
     * @return array
     */
    public function update(array $data, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este rol no existe',
                'data'    => [],
            ];
        }

        // code se excluye deliberadamente: cambiar el identificador rompería el sistema.
        $roleData = [];

        if (array_key_exists('name', $data)) $roleData['name'] = $data['name'];
        if (array_key_exists('description', $data)) $roleData['description'] = $data['description'] ?? null;

        // Sin campos válidos: retorna éxito silencioso con el registro sin modificar.
        if (empty($roleData)) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay datos para actualizar',
                'data'    => $role,
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
            'error'   => false,
            'code'    => 200,
            'message' => 'Rol actualizado con éxito',
            // fresh() recarga desde BD para devolver los datos ya persistidos.
            'data'    => $role->fresh(),
        ];
    }

    /**
     * Elimina un rol por su ID.
     *
     * Verifica que no tenga usuarios asignados antes de eliminar usando exists()
     * en lugar de count(): más eficiente porque para en el primer resultado encontrado.
     *
     * @param  mixed  $id  ID del rol.
     * @return array
     */
    public function delete($id)
    {
        // 1) Roles internos del sistema: no deben eliminarse desde la UI ni por ID directo.
        $excludedCodes = ['PENDIENTE', 'APRENDIZ', 'SCANNER'];

        // 2) Buscar el rol por ID.
        $role = Role::find($id);

        // 3) Validar existencia.
        if (!$role) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este rol no existe',
                'data'    => [],
            ];
        }

        // 4) Bloquear eliminación de roles internos, incluso si se encuentran por ID.
        if (in_array($role->code, $excludedCodes, true)) {
            return [
                'error'   => true,
                'code'    => 409,
                'message' => 'No se puede eliminar un rol interno del sistema',
                'data'    => [],
            ];
        }

        // 5) Validar integridad referencial (regla de negocio):
        //    Si el rol está asignado a usuarios, no se debe permitir eliminarlo.
        //    exists() es más eficiente que count(): no cuenta todo, solo verifica 1 coincidencia.
        if ($role->users()->exists()) {
            return [
                'error'   => true,
                'code'    => 409, // Conflicto: no se puede eliminar por dependencias existentes.
                'message' => 'No se puede eliminar un rol asignado a usuarios',
                'data'    => [],
            ];
        }

        // 6) Guardar el ID antes de eliminar para auditoría/evento.
        $deletedId = $role->id;

        // 7) Eliminar el rol (sin usuarios asignados).
        $role->delete();

        // 8) Auditoría / notificación.
        event(new ResourceChanged(
            'eliminar',
            Role::class,
            $deletedId,
            Auth::id(),
            'Rol'
        ));

        // 9) Respuesta exitosa.
        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Rol eliminado con éxito',
            'data'    => [],
        ];
    }

    /**
     * Sincroniza los permisos de un rol reemplazando la asignación completa.
     *
     * Usa syncPermissions() de Spatie: elimina todos los permisos actuales del rol
     * y asigna exactamente los enviados en $permissionIds. Es una operación destructiva.
     * Los permisos se filtran por guard_name del rol para evitar asignar permisos de otro guard.
     *
     * @param  mixed  $roleId         ID del rol.
     * @param  array  $permissionIds  IDs de los permisos a asignar (reemplaza los existentes).
     * @return array
     */
    public function syncPermissions($roleId, array $permissionIds)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este rol no existe',
                'data'    => [],
            ];
        }

        // Filtra por guard_name del rol: evita asignar permisos de un guard diferente al del rol.
        $permissions = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->whereIn('id', $permissionIds)
            ->get();

        // syncPermissions() de Spatie: reemplaza TODOS los permisos actuales del rol.
        // Equivale a detachAllPermissions() + attachPermissions($permissions).
        $role->syncPermissions($permissions);

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Permisos del rol actualizados con éxito',
            // load() recarga la relación permissions en el modelo ya cargado (sin segunda query al rol).
            'data'    => $role->load('permissions:id,name,display_name,group'),
        ];
    }
}
