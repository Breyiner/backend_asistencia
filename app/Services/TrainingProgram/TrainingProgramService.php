<?php

namespace App\Services\TrainingProgram;

use App\Models\TrainingProgram;

class TrainingProgramService
{
    public function getAll() {

        $programs = TrainingProgram::with(['qualificationLevel', 'area'])->get();

        if ($programs->isEmpty()){
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay programas de formación registrados",
                "data" => $programs,
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programas de formación obtenidos exitosamente",
            "data" => $programs,
        ];

    }

    public function getById($id) {
        $program = TrainingProgram::with(['qualificationLevel', 'area'])->find($id);

        if (!$program){
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación obtenido exitosamente",
            "data" => $program,
        ];
    }

    public function create(array $data) {
        $program = TrainingProgram::create(
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'duration' => $data['duration'],
                'qualification_level_id' => $data['qualification_level_id'],
                'area_id' => $data['area_id'],
            ]
        );

        return [
            "error" => false,
            "code" => 201,
            "message" => "Programa de formación creado exitosamente",
            "data" => $program,
        ];
    }

    public function update(array $data, $id) {
        $program = TrainingProgram::find($id);

        if (!$program){
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        $programData = [];

        if (array_key_exists('name', $data)){
            $programData['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)){
            $programData['description'] = $data['description'] ?? null;
        }
        if (array_key_exists('duration', $data)){
            $programData['duration'] = $data['duration'];
        }
        if (array_key_exists('qualification_level_id', $data)){
            $programData['qualification_level_id'] = $data['qualification_level_id'];
        }
        if (array_key_exists('area_id', $data)){
            $programData['area_id'] = $data['area_id'];
        }   

        if (empty($programData)){
            return [
                "error" => false,
                "code" => 200,
                "message" => "No hay datos para actualizar",
                "data" => $program,
            ];
        }

        $program->update($programData);
        
        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación actualizado exitosamente",
            "data" => $program,
        ];
    }

    public function delete($id) {
        $program = TrainingProgram::find($id);

        if (!$program){
            return [
                "error" => true,
                "code" => 404,
                "message" => "Programa de formación no encontrado",
            ];
        }

        $program->delete();

        return [
            "error" => false,
            "code" => 200,
            "message" => "Programa de formación eliminado exitosamente",
        ];
    }
}
