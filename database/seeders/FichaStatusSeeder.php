<?php

namespace Database\Seeders;

use App\Models\FichaStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FichaStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Lectiva', 
                'description' => 'Ficha en etapa lectiva',
            ],
            [
                'name' => 'Productiva',
                'description' => 'Ficha en etapa productiva',
            ],
            [
                'name' => 'Sin iniciar',
                'description' => 'Ficha que no ha iniciado',
            ],
            [
                'name' => 'Finalizada',
                'description' => 'Ficha que ha finalizado',
            ],
            [
                'name' => 'Suspendida',
                'description' => 'Ficha que ha sido suspendida',
            ],
            [
                'name' => 'Cancelada',
                'description' => 'Ficha que ha sido cancelada',
            ],
        ];

        foreach ($statuses as $status) {
            FichaStatus::create($status);
        }
    }
}
