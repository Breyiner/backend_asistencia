<?php

namespace App\Services\User;

use App\Events\ResourceChanged;
use App\Events\UserCreated;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    private const ROLES_WITH_AREAS = ['ADMIN', 'COORDINADOR', 'GESTOR_FICHAS', 'INSTRUCTOR'];

    public function getAll($perPage = 10)
    {
        $query = User::select([
            'id',
            'email',
            'document_number',
            'status_id'
        ])
            ->whereNull('type')
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name,code',
                'areas:id,name',
            ]);

        if (request()->filled('email')) {
            $query->where('email', 'like', "%" . request('email') . "%");
        }

        if (request()->filled('document_number')) {
            $query->where('document_number', 'like', "%" . request('document_number') . "%");
        }

        if (request()->filled('status_name')) {
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', request('status_name') . "%");
            });
        }

        if (request()->filled('document_type_id')) {
            $query->where('document_type_id', request('document_type_id'));
        }

        if (request()->filled('first_name')) {
            $query->whereHas('profile', function ($q) {
                $q->where('first_name', 'like', "%" . request('first_name') . "%");
            });
        }

        if (request()->filled('last_name')) {
            $query->whereHas('profile', function ($q) {
                $q->where('last_name', 'like', "%" . request('last_name') . "%");
            });
        }

        if (request()->filled('telephone_number')) {
            $query->whereHas('profile', function ($q) {
                $q->where('telephone_number', 'like', "%" . request('telephone_number') . "%");
            });
        }

        if (request()->filled('role_name')) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'like', "%" . request('role_name') . "%");
            });
        }

        if (request()->filled('area_id')) {
            $query->whereHas('areas', function ($q) {
                $q->where('areas.id', request('area_id'));
            });
        }

        $users = $query->paginate($perPage);

        $items = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->profile?->first_name ?? '',
                'last_name' => $user->profile?->last_name ?? '',
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'areas' => $user->areas->pluck('name')->toArray(),
                'status' => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number
            ];
        });

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuarios obtenidos con éxito",
            "data" => $items,
            "paginate" => [
                "current_page" => $users->currentPage(),
                "per_page" => $users->perPage(),
                "total" => $users->total(),
                "last_page" => $users->lastPage(),
                "from" => $users->firstItem(),
                "to" => $users->lastItem(),
            ]
        ];
    }

    public function getAllByRoleId(int $roleId)
    {
        $users = User::select([
            'id',
            'email',
            'document_number',
            'status_id'
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
            return [
                'id' => $user->id,
                'first_name' => $user->profile?->first_name ?? '',
                'last_name' => $user->profile?->last_name ?? '',
                'full_name' => "{$user->profile?->first_name} {$user->profile?->last_name}",
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'status' => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number
            ];
        });

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuarios obtenidos con éxito",
            "data" => $items
        ];
    }

    public function getAllByAreaId(int $areaId)
    {
        $users = User::select([
            'id',
            'email',
            'document_number',
            'status_id'
        ])
            ->whereNull('type')
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name,code',
            ])
            ->whereHas('areas', function ($q) use ($areaId) {
                $q->where('areas.id', $areaId);
            })
            ->get();

        $items = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->profile?->first_name ?? '',
                'last_name' => $user->profile?->last_name ?? '',
                'full_name' => "{$user->profile?->first_name} {$user->profile?->last_name}",
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'status' => $user->status?->name ?? 'Sin estado',
                'document_number' => $user->document_number
            ];
        });

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuarios del área obtenidos con éxito",
            "data" => $items
        ];
    }

    public function getById($id)
    {
        $user = User::whereNull('type')
            ->with(['profile', 'status', 'roles:id,name,code', 'areas:id,name'])
            ->find($id);

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];
        }

        $items = [
            'id' => $user->id,
            'first_name' => $user->profile?->first_name ?? '',
            'last_name' => $user->profile?->last_name ?? '',
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'role_ids' => $user->roles->pluck('id')->toArray(),
            'areas' => $user->areas->pluck('name')->toArray(),
            'area_ids' => $user->areas->pluck('id')->toArray(),
            'status_id' => $user->status?->id,
            'status' => $user->status?->name ?? 'Sin estado',
            'document_number' => $user->document_number,
            'document_type_id' => $user->document_type_id,
            'document_type_name' => $user->documentType?->name ?? '',
            'telephone_number' => $user->profile?->telephone_number ?? '',
            'created_at' => $user->created_at?->toDateString(),
            'updated_at' => $user->updated_at?->toDateString(),
        ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuario obtenido con éxito",
            "data" => $items
        ];
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $password = Str::password(12);

            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($password),
                'document_number' => $data['document_number'],
                'document_type_id' => $data['document_type_id'],
            ]);

            $profile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'telephone_number' => $data['telephone_number'],
            ]);

            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($user, $data['roles']);
            } else {
                $user->assignRole('Pendiente');
            }

            if (array_key_exists('area_ids', $data) && !empty($data['area_ids'])) {
                $user->loadMissing('roles:id,code');
                if ($this->userCanHaveAreas($user)) {
                    $user->areas()->sync($data['area_ids']);
                } else {
                    $user->areas()->detach();
                }
            }

            DB::commit();

            event(new UserCreated([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $password,
                'created_at' => $user->created_at,
            ]));

            event(new Registered($user));

            event(new ResourceChanged(
                'crear',
                User::class,
                $user->id,
                Auth::id(),
                'Usuario',
            ));

            return [
                'error' => false,
                'code' => 201,
                'data' => compact('user', 'password', 'profile'),
                'message' => 'Usuario creado con éxito',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Ocurrió un error al crear el usuario {$e->getMessage()}",
            ];
        }
    }

    public function update(array $data, string $id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);

            if (!$user) {
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este usuario no existe",
                ];
            }

            $userData = [];

            if (array_key_exists('document_number', $data)) {
                $userData['document_number'] = $data['document_number'];
            }

            if (array_key_exists('document_type_id', $data)) {
                $userData['document_type_id'] = $data['document_type_id'];
            }

            if (array_key_exists('email', $data) && $data['email'] !== $user->email) {
                $userData['email'] = $data['email'];
                $userData['email_verified_at'] = null;
            }

            if (array_key_exists('status_id', $data)) {
                $userData['status_id'] = $data['status_id'];
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

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

            if (!empty($profileData)) {
                $user->profile()->update($profileData);
            }

            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($user, $data['roles']);
            } else {
                $user->assignRole('Pendiente');
            }

            if (array_key_exists('area_ids', $data)) {
                $user->loadMissing('roles:id,code');
                if ($this->userCanHaveAreas($user)) {
                    $user->areas()->sync($data['area_ids']);
                } else {
                    $user->areas()->detach();
                }
            }

            DB::commit();

            return [
                'error' => false,
                'code' => 200,
                'data' => [
                    'user' => $user->fresh(),
                    'profile' => $user->profile
                ],
                'message' => 'Usuario actualizado con éxito',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Error al actualizar: {$e->getMessage()}",
            ];
        }
    }

    public function updateRoles(array $roleIds, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];
        }

        $this->syncUserRoles($user, $roleIds);

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
            "error" => false,
            "code" => 200,
            "message" => "Roles del usuario actualizados con éxito",
        ];
    }

    public function updateAreas(array $areaIds, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];
        }

        $user->loadMissing('roles:id,code');

        if (!$this->userCanHaveAreas($user)) {
            return [
                "error" => true,
                "code" => 403,
                "message" => "Este usuario no puede tener áreas asignadas.",
            ];
        }

        $user->areas()->sync($areaIds);

        event(new ResourceChanged(
            'actualizar',
            User::class,
            $user->id,
            Auth::id(),
            'Usuario',
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Áreas del usuario actualizadas con éxito",
        ];
    }

    private function syncUserRoles(User $user, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
    }

    private function userCanHaveAreas(User $user): bool
    {
        $codes = $user->roles->pluck('code')->filter()->values()->toArray();

        return !empty(array_intersect(self::ROLES_WITH_AREAS, $codes));
    }
}