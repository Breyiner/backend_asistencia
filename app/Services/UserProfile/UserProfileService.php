<?php

namespace App\Services\UserProfile;

use App\Events\ResourceChanged;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserProfileService
{
    public static function getAll()
    {

        $profiles = UserProfile::all();

        if (count($profiles) == 0)
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay perfiles de usuarios registrados",
                "data" => $profiles
            ];


        return [
            "error" => false,
            "code" => 200,
            "message" => "Perfiles obtenidos con éxito",
            "data" => $profiles
        ];
    }

    public function getProfile($user_id)
    {

        $user = User::with('profile', 'roles')->find($user_id);

        if (!$user)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este perfil de usuario no existe",
            ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Perfil obtenido con éxito",
            "data" => $user
        ];
    }

    public function getProfileByUser($user_id)
    {

        $user = User::find($user_id);

        if (!$user)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este usuario no existe",
            ];

        $profile = UserProfile::where('user_id', $user_id)->first();

        if (!$profile)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este perfil de usuario no existe",
            ];

        return [
            "error" => false,
            "code" => 200,
            "message" => "Perfil del usuario obtenido con éxito",
            "data" => $profile
        ];
    }

    public static function createProfile(array $data)
    {

        $profile = UserProfile::create([
            'user_id' => $data['user_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'telephone_number' => $data['telephone_number'],
        ]);

        if ($profile->id)
            return [
                'error' => false,
                'code' => 201,
                'message' => 'Perfil creado con éxito',
            ];

        event(new ResourceChanged(
            'crear',
            UserProfile::class,
            $profile->id,
            Auth::id(),
            'Perfil de usuario',
        ));

        return [
            'error' => true,
            'code' => 500,
            'message' => 'Error al intentar crear el pefríl',
        ];
    }

    public function updateProfile(array $data, $user_id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($user_id);
            if (!$user) {
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este usuario no existe",
                ];
            }

            $profile = UserProfile::where('user_id', $user_id)->first();
            if (!$profile) {
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este perfil de usuario no existe",
                ];
            }

            $profileData = [];

            if (array_key_exists('first_name', $data)) {
                $profileData['first_name'] = $data['first_name'];
            }
            if (array_key_exists('last_name', $data)) {
                $profileData['last_name'] = $data['last_name'];
            }
            if (array_key_exists('phone_number', $data)) {
                $profileData['phone_number'] = $data['phone_number'];
            }

            if (!empty($profileData)) {
                $profile->update($profileData);
            }

            DB::commit();

            event(new ResourceChanged(
                'actualizar',
                UserProfile::class,
                $profile->id,
                Auth::id(),
                'Perfil de usuario',
            ));

            return [
                "error" => false,
                "code" => 200,
                "data" => [
                    'user' => $user->fresh(),
                    'profile' => $profile->fresh()
                ],
                "message" => "Perfil actualizado con éxito",
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                "error" => true,
                "code" => 500,
                "message" => "Error al actualizar perfil: {$e->getMessage()}",
            ];
        }
    }

}
