<?php

namespace Database\Seeders;

use App\Models\Phase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PhaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phases = [
            [
                'name' => 'Inducción',
                'description' => 'Fase de bienvenida y orientación inicial.'
            ],
            [
                'name'=> 'Análisis',
                'description' => 'Análisis de necesidades y diagnóstico.'
            ],
            [
                'name'=> 'Desarrollo',
                'description' => 'Desarrollo de competencias técnicas.'
            ],
            [
                'name'=> 'Evaluación',
                'description' => 'Evaluación final de competencias.'
            ],
        ];

        foreach ($phases as $phase) {
            Phase::create([
                'name' => $phase['name'],
                'description' => $phase['description'],
            ]);
        }
    }
}
