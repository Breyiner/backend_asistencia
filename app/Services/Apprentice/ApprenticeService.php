<?php

namespace App\Services\Apprentice;

use App\Events\ResourceChanged;
use App\Models\Apprentice;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprenticeService
{

    public function getAll()
    {

        $apprentices = Apprentice::with('ficha', 'profile')->get();

        if ($apprentices->isEmpty()) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "Aprendices obtenidos con éxito"
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Aprendices obtenidos con éxito",
            "data" => $apprentices
        ];
    }

    public function getById($id)
    {

        $apprentice = Apprentice::with(['profile', 'ficha'])->find($id);

        if (!$apprentice) {
            return [
                "error" => false,
                "code" => 200,
                "message" => "Aprendiz no encontrado"
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Aprendiz obtenido con éxito",
            "data" => $apprentice
        ];
    }


    public function create($data)
    {

        try {

            DB::beginTransaction();

            $apprentice = Apprentice::create([

                'document_type_id' => $data['document_type_id'],
                'document_number' => $data['document_number'],
                'email' => $data['email'],
                'password' => null,
                'status_id' => 1,
                'ficha_id' => $data['ficha_id']

            ]);

            $apprentice->profile()->create([

                'user_id' => $apprentice->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'telephone_number' => $data['telephone_number'],
                'birth_date' => $data['birth_date']

            ]);

            $apprentice->roles()->attach(4);

            DB::commit();

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
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Ocurrió un error al crear el aprendiz {$e->getMessage()}",
            ];
        }
    }

    public function update(array $data, String $id)
    {
        try {
            DB::beginTransaction();

            $apprentice = Apprentice::find($id);

            if (!$apprentice)
                return [
                    "error" => true,
                    "code" => 404,
                    "message" => "Este aprendiz no existe",
                ];

            $apprenticeData = [];

            if (array_key_exists('document_number', $data)) {
                $apprenticeData['document_number'] = $data['document_number'];
            }

            if (array_key_exists('document_type_id', $data)) {
                $apprenticeData['document_type_id'] = $data['document_type_id'];
            }

            if (array_key_exists('email', $data) && $data['email'] !== $apprentice->email) {
                $apprenticeData['email'] = $data['email'];
                $apprenticeData['email_verified_at'] = null;
            }

            if (array_key_exists('status_id', $data)) {
                $apprenticeData['status_id'] = $data['status_id'];
            }

            if (array_key_exists('ficha_id', $data)) {
                $apprenticeData['ficha_id'] = $data['ficha_id'];
            }

            if (!empty($apprenticeData)) {
                $apprentice->update($apprenticeData);
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
            if (array_key_exists('birth_date', $data)) {
                $profileData['birth_date'] = $data['birth_date'];
            }

            if (!empty($profileData)) {
                $apprentice->profile()->update($profileData);
            }

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
            DB::rollBack();
            return [
                'error' => true,
                'code' => 500,
                'message' => "Error al actualizar: {$e->getMessage()}",
            ];
        }
    }

    public function destroy($id)
    {

        $apprentice = Apprentice::find($id);

        if (!$apprentice)
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este aprendiz no existe",
            ];

        $apprentice->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Aprendiz eliminado con éxito",
        ];
    }
}
