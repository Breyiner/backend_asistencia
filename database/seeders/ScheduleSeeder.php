<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
    ["description" => "Horario ficha 1 - fase 1", "ficha_term_id" => 1],
    ["description" => "Horario ficha 2 - fase 1", "ficha_term_id" => 2],
    ["description" => "Horario ficha 3 - fase 1", "ficha_term_id" => 3],
    ["description" => "Horario ficha 4 - fase 1", "ficha_term_id" => 4],
    ["description" => "Horario ficha 1 - fase 2", "ficha_term_id" => 5],
    ["description" => "Horario ficha 2 - fase 2", "ficha_term_id" => 6],
    ["description" => "Horario ficha 3 - fase 2", "ficha_term_id" => 7],
];


        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}
