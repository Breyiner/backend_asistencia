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
            // Ficha 1 (2894667)
            [
                "term_id" => 1,
                "ficha_id" => 1,
                "phase_id" => 1,
                "start_date" => "2024-02-01",
                "end_date" => "2024-05-01",
                "is_current" => true
            ],
            [
                "term_id" => 5,
                "ficha_id" => 1,
                "phase_id" => 2,
                "start_date" => "2024-05-02",
                "end_date" => "2024-08-01",
                "is_current" => false
            ],
            // Ficha 2 (2994281)
            [
                "term_id" => 2,
                "ficha_id" => 2,
                "phase_id" => 1,
                "start_date" => "2024-02-01",
                "end_date" => "2024-05-01",
                "is_current" => true
            ],
            [
                "term_id" => 6,
                "ficha_id" => 2,
                "phase_id" => 2,
                "start_date" => "2024-05-02",
                "end_date" => "2024-08-01",
                "is_current" => false
            ],
            // Ficha 3 (3065369)
            [
                "term_id" => 3,
                "ficha_id" => 3,
                "phase_id" => 1,
                "start_date" => "2024-02-01",
                "end_date" => "2024-05-01",
                "is_current" => true
            ],
            [
                "term_id" => 7,
                "ficha_id" => 3,
                "phase_id" => 2,
                "start_date" => "2024-05-02",
                "end_date" => "2024-08-01",
                "is_current" => false
            ],
            // Ficha 4 (3315656)
            [
                "term_id" => 4,
                "ficha_id" => 4,
                "phase_id" => 1,
                "start_date" => "2024-02-01",
                "end_date" => "2024-05-01",
                "is_current" => true
            ],
            [
                "term_id" => 7,
                "ficha_id" => 4,
                "phase_id" => 2,
                "start_date" => "2024-05-02",
                "end_date" => "2024-08-01",
                "is_current" => false
            ],
        ];

        foreach ($fichaTerms as $fichaTerm) {
            FichaTerm::create($fichaTerm);
        }
    }
}
