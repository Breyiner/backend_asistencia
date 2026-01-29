<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Diurna',
            ],
            [
                'name' => 'Nocturna',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
