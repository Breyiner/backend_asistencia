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
                'name' => 'Mañana',
                'start_time' => '06:30',
                'end_time' => '12:30',
            ],
            [
                'name' => 'Tarde',
                'start_time' => '12:30',
                'end_time' => '18:30',
            ],
            [
                'name' => 'Nocturna',
                'start_time' => '18:30',
                'end_time' => '21:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }
    }
}
