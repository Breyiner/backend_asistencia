<?php

namespace Database\Seeders;

use App\Models\Ficha;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FichaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fichas = [
            [
                'gestor_id' => 2,
                'ficha_number' => '2894667',
                'start_date' => '2024-02-01',
                'end_date' => '2024-08-01',
                'training_program_id' => 4,
                'status_id' => 1,
            ],
            [
                'gestor_id' => 2,
                'ficha_number' => '67890',
                'start_date' => '2024-03-01',
                'end_date' => '2024-09-01',
                'training_program_id' => 2,
                'status_id' => 2,
            ],
        ];

        foreach ($fichas as $fichaData) {
            Ficha::create($fichaData);
        }
    }
}
