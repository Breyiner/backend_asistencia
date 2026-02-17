<?php

namespace Database\Seeders;

use App\Models\NoClassReason;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoClassReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            [
                'name' => 'Festivo nacional',
                'description' => 'Día festivo oficial declarado por el gobierno nacional.',
            ],
            [
                'name' => 'Novedad Académica',
                'description' => 'Suspensión de actividades por novedad académica presentada.',
            ],
            [
                'name' => 'Actividad institucional',
                'description' => 'Evento o actividad especial organizada por el SENA.',
            ],
            [
                'name' => 'Mantenimiento de instalaciones',
                'description' => 'Cierre de instalaciones por obras o mantenimiento.',
            ],
        ];

        foreach ($reasons as $reason) {
            NoClassReason::create($reason);
        }
    }
}