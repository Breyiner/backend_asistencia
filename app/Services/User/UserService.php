<?php

namespace App\Services\User;

use App\Events\ResourceChanged;
use App\Events\UserCreated;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserStatus;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function getAll($perPage = 10)
    {
        $query = User::select([
            'id',
            'email',
            'document_number',
            'status_id'
        ])
            ->with([
                'profile:id,user_id,first_name,last_name',
                'status:id,name',
                'roles:id,name'
            ]);

        // Filtros (mantén todos)
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

        $users = $query->paginate($perPage);

        // Transforma a formato plano para frontend
        $items = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->profile?->first_name ?? '',
                'last_name' => $user->profile?->last_name ?? '',
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


    public function getById($id)
    {

        $user = User::find($id);

        if (!$user)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuario obtenido con éxito",
            "data" => $$user
        ];
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            $telephoneNumber = $data['telephone_number'];
            $email = $data['email'];
            $documentNumber = $data['document_number'];
            $documentTypeId = $data['document_type_id'];

            $password = Str::password(12);

            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'document_number' => $documentNumber,
                'document_type_id' => $documentTypeId,
            ]);

            $profile = UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'telephone_number' => $telephoneNumber
            ]);

            if (array_key_exists('roles', $data)) {
                $this->syncUserRoles($user, $data['roles']);
            } else {
                $user->assignRole('Pendiente');
            }

            DB::commit();

            event(new UserCreated([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
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

    public function update(array $data, String $id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);

            if (!$user)
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este usuario no existe",
                ];

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

        if (!$user)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];

        $this->syncUserRoles($user, $roleIds);

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

    public function updatePassword(array $data, $id)
    {

        $user = User::find($id);

        if (!$user)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];

        if ($data['current_password'])
            if (!Hash::check($data['current_password'], $user->password))
                return [
                    "error" => true,
                    "code" => 401,
                    "message" => "Contraseña incorrecta"
                ];


        $user->update([
            "password" => Hash::make($data['new_password'])
        ]);

        return [
            "error" => false,
            "code" => 200,
            "message" => "Contraseña actualizada con éxito",
        ];
    }

    public function destroy($id)
    {

        $user = User::find($id);

        if (!$user) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];
        }

        $user->delete();

        event(new ResourceChanged(
            'eliminar',
            User::class,
            $user->id,
            Auth::id(),
            'Usuario',
        ));

        return [
            "error" => false,
            "code" => 200,
            "message" => "Usuario eliminado con éxito",
        ];
    }

    private function syncUserRoles(User $user, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
    }
}
