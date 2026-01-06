<?php

namespace Database\Seeders;

use App\Models\Day;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            ['name' => 'Lunes', 'day_number' => 1],
            ['name' => 'Martes', 'day_number' => 2],
            ['name' => 'Miércoles', 'day_number' => 3],
            ['name' => 'Jueves', 'day_number' => 4],
            ['name' => 'Viernes', 'day_number' => 5],
            ['name' => 'Sábado', 'day_number' => 6],
            ['name' => 'Domingo', 'day_number' => 7],
        ];

        foreach ($days as $day) {
            Day::create($day);
        }
    }
}
