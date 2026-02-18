<?php

namespace App\Services\UserProfile;

use App\Events\ResourceChanged;
use App\Models\User;
use App\Models\UserProfile;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lógica de negocio para la gestión de perfiles de usuario.
 *
 * Un UserProfile está en relación 1:1 con User y almacena datos personales
 * (nombre, apellido, teléfono). Se crea junto con el User en UserService::create()
 * y se actualiza de forma independiente aquí.
 *
 * Nota: createProfile() se mantiene como método de instancia (no static) para
 * consistencia con el resto del servicio y compatibilidad con inyección de dependencias.
 */
class UserProfileService
{
    /**
     * Retorna todos los perfiles de usuario.
     *
     * Uso administrativo interno. Sin paginación ni filtros porque es un
     * método de soporte, no un endpoint público de listado.
     *
     * @return array
     */
    public function getAll()
    {
        // orderBy para consistencia de respuesta entre llamadas.
        $profiles = UserProfile::orderBy('id')->get();

        // isEmpty() es más idiomático que count() == 0 en Laravel Collections.
        if ($profiles->isEmpty()) {
            return [
                'error'   => false,
                'code'    => 200,
                'message' => 'No hay perfiles de usuarios registrados',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Perfiles obtenidos con éxito',
            'data'    => $profiles,
        ];
    }

    /**
     * Retorna el usuario con su perfil y roles cargados por ID de usuario.
     *
     * Devuelve el modelo User completo (no solo el perfil) porque el frontend
     * necesita datos del User (email, estado) junto con el perfil para la vista.
     *
     * @param  mixed  $user_id  ID del usuario.
     * @return array
     */
    public function getProfile($user_id)
    {
        // with('profile', 'roles'): carga relaciones necesarias para la vista de perfil.
        $user = User::with('profile', 'roles')->find($user_id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este perfil de usuario no existe',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Perfil obtenido con éxito',
            'data'    => $user,
        ];
    }

    /**
     * Retorna solo el UserProfile de un usuario por su ID.
     *
     * A diferencia de getProfile(), devuelve únicamente el modelo UserProfile
     * sin el User ni los roles. Uso: endpoints que solo necesitan datos del perfil.
     *
     * Verifica la existencia del User antes del perfil para dar errores más precisos.
     *
     * @param  mixed  $user_id  ID del usuario.
     * @return array
     */
    public function getProfileByUser($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este usuario no existe',
                'data'    => [],
            ];
        }

        // Busca el perfil por user_id (relación 1:1, podría usarse $user->profile con loadMissing).
        $profile = UserProfile::where('user_id', $user_id)->first();

        if (!$profile) {
            return [
                'error'   => true,
                'code'    => 404,
                'message' => 'Este perfil de usuario no existe',
                'data'    => [],
            ];
        }

        return [
            'error'   => false,
            'code'    => 200,
            'message' => 'Perfil del usuario obtenido con éxito',
            'data'    => $profile,
        ];
    }

    /**
     * Crea un nuevo perfil de usuario.
     *
     * Generalmente se llama desde UserService::create() dentro de una transacción.
     * El evento de auditoría se dispara después del create() exitoso.
     *
     * @param  array  $data  Datos validados (user_id, first_name, last_name, telephone_number).
     * @return array
     */
    public function createProfile(array $data)
    {
        $profile = UserProfile::create([
            'user_id'          => $data['user_id'],
            'first_name'       => $data['first_name'],
            'last_name'        => $data['last_name'],
            'telephone_number' => $data['telephone_number'],
        ]);

        // El evento se dispara tras la creación exitosa (no antes del return de éxito).
        event(new ResourceChanged(
            'crear',
            UserProfile::class,
            $profile->id,
            Auth::id(),
            'Perfil de usuario',
        ));

        return [
            'error'   => false,
            'code'    => 201,
            'message' => 'Perfil creado con éxito',
            'data'    => $profile,
        ];
    }

    /**
     * Actualiza el perfil de un usuario existente.
     *
     * Verifica que tanto el User como su UserProfile existan antes de actualizar.
     * Solo actualiza los campos presentes en $data (PATCH semántico).
     * El evento de auditoría se dispara fuera del try/catch para no revertir por fallo de evento.
     *
     * @param  array  $data     Campos a actualizar (first_name, last_name, telephone_number).
     * @param  mixed  $user_id  ID del usuario cuyo perfil se actualiza.
     * @return array
     */
    public function updateProfile(array $data, $user_id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($user_id);

            if (!$user) {
                return [
                    'error'   => true,
                    'code'    => 404,
                    'message' => 'Este usuario no existe',
                    'data'    => [],
                ];
            }

            $profile = UserProfile::where('user_id', $user_id)->first();

            if (!$profile) {
                return [
                    'error'   => true,
                    'code'    => 404,
                    'message' => 'Este perfil de usuario no existe',
                    'data'    => [],
                ];
            }

            // Construye el array solo con los campos enviados en el request.
            $profileData = [];

            if (array_key_exists('first_name', $data))       $profileData['first_name']       = $data['first_name'];
            if (array_key_exists('last_name', $data))        $profileData['last_name']         = $data['last_name'];
            // Nota: el campo en BD es 'telephone_number', no 'phone_number.
            // Si el Request envía 'phone_number', mapear aquí: $data['phone_number'] → 'telephone_number'.
            if (array_key_exists('telephone_number', $data)) $profileData['telephone_number']  = $data['telephone_number'];

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
                'error'   => false,
                'code'    => 200,
                'message' => 'Perfil actualizado con éxito',
                'data'    => [
                    'user'    => $user->fresh(),
                    // fresh() recarga el perfil desde BD para devolver los datos ya persistidos.
                    'profile' => $profile->fresh(),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'error'   => true,
                'code'    => 500,
                'message' => "Error al actualizar perfil: {$e->getMessage()}",
                'data'    => [],
            ];
        }
    }
}
