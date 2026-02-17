<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            [
                'name' => 'Trimestre 1',
            ],
            [
                'name' => 'Trimestre 2',
            ],
            [
                'name' => 'Trimestre 3',
            ],
            [
                'name' => 'Trimestre 4',
            ],
            [
                'name' => 'Trimestre 5',
            ],
            [
                'name' => 'Trimestre 6',
            ],
            [
                'name' => 'Trimestre 7',
            ],
        ];

        foreach ($terms as $term) {
            Term::create([
                'name' => $term['name'],
            ]);
        }
    }
}
