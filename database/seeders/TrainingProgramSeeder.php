<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrainingProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Programación Web',
                'description' => 'Aprende a desarrollar aplicaciones web modernas.',
                'duration' => 21,
                'qualification_level_id' => 1,
                'area_id' => 1,
            ],
            [
                'name' => 'Análisis de Datos',
                'description' => 'Curso completo sobre análisis y visualización de datos.',
                'duration' => 21,
                'qualification_level_id' => 2,
                'area_id' => 2,
            ],
            [
                'name' => 'Marketing Digital',
                'description' => 'Estrategias y herramientas para el marketing en línea.',
                'duration' => 21,
                'qualification_level_id' => 1,
                'area_id' => 3,
            ],
            [
                'name' => 'Análisis y desarrollo de software',
                'description' => 'Curso completo sobre análisis y desarrollo de software.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 1,
            ]
        ];

        foreach ($programs as $program) {
            TrainingProgram::create($program);
        }
    }
}
