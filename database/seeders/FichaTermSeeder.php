<?php

namespace Database\Seeders;

use App\Models\FichaTerm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FichaTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fichaTerms = [

            [
                "term_id" => 1,
                "ficha_id" => 1,
                "phase_id" => 2,
                "start_date" => "2024-12-13",
                "end_date" => "2025-03-13",
                "is_current" => true
            ],

        ];

        foreach ($fichaTerms as $fichaTerm) {
            FichaTerm::create($fichaTerm);
        }
    }
}
