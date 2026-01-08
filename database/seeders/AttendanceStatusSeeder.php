<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'presente',
                'description' => 'Aprendiz asistió completo a la clase',
            ],
            [
                'name' => 'ausente',
                'description' => 'Aprendiz no asistió a la clase',
            ],
            [
                'name' => 'ausencia_justificada',
                'description' => 'Ausencia con justificación médica o permiso',
            ],
            [
                'name' => 'tardanza',
                'description' => 'Aprendiz llegó tarde a la clase',
            ],
            [
                'name' => 'salida_anticipada',
                'description' => 'Aprendiz salió antes de finalizar la clase',
            ],
        ];

        foreach ($statuses as $status) {
            AttendanceStatus::create($status);
        }
    }
}
