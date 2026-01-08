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

            [
                "description" => "Horario ficha 1 - trimestre 1 (fase 2)",
                "ficha_term_id" => 1
            ]

        ];

        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}
