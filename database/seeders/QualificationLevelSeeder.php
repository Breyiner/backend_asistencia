<?php

namespace Database\Seeders;

use App\Models\QualificationLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QualificationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $quealificationLevels = [
            ['name' => 'Auxiliar', 'description' => null],
            ['name' => 'Técnico', 'description' => null],
            ['name' => 'Tecnólogo', 'description' => null],
        ];

        foreach ($quealificationLevels as $level) {
            QualificationLevel::updateOrCreate(['name' => $level['name']], ['description' => $level['description']]);
        }
    }
}
