<?php

namespace App\Services\Apprentice;

use App\Events\ResourceChanged;
use App\Models\Apprentice;
use App\Models\Role;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprenticeService
{

    public function getAll($perPage = 10)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

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

        if (request()->filled('email')) {
            $query->where('email', 'like', '%' . request('email') . '%');
        }

        if (request()->filled('document_number')) {
            $query->where('document_number', 'like', '%' . request('document_number') . '%');
        }

        if (request()->filled('status_name')) {
            $query->whereHas('status', function ($q) {
                $q->where('name', 'like', request('status_name') . '%');
            });
        }

        if (request()->filled('document_type_id')) {
            $query->where('document_type_id', request('document_type_id'));
        }

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
            $query->whereHas('ficha', function ($q) {
                $q->where('ficha_number', 'like', '%' . request('ficha_number') . '%');
            });
        }

        $apprentices = $query->paginate($perPage);

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

    public function getById($id)
    {
        $userId = Auth::id();
        $roleCode = request()->attributes->get('acting_role_code');

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

        $apprentice = $query->find($id);

        if (!$apprentice) {
            return [
                "error" => true,
                "code" => 404,
                "message" => "Este aprendiz no existe",
            ];
        }

        $items = [
            'id' => $apprentice->id,
            'first_name' => $apprentice->profile?->first_name ?? '',
            'last_name' => $apprentice->profile?->last_name ?? '',
            'email' => $apprentice->email,
            'roles' => $apprentice->roles->pluck('name')->toArray(),
            'role_ids' => $apprentice->roles->pluck('id')->toArray(),
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

            $apprenticeRoleId = Role::idByCode('APRENDIZ');

            $apprentice->roles()->syncWithoutDetaching([$apprenticeRoleId]);

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
