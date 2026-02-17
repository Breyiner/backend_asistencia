<?php

namespace Database\Seeders;

use App\Models\ScheduleSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSessionSeeder extends Seeder
{
    public function run(): void
    {
        // 3 sesiones por franja (mañana/tarde) × 2 franjas × 7 ficha_terms = 42 sesiones total
        $fichaTermIds = [1,2,3,4,5,6,7]; // De FichaTermSeeder
        
        $sessions = [];
        
        foreach ($fichaTermIds as $fichaTermId) {
            // Mañana: Lun(1), Mie(3), Vie(5)
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,  // Cada fichaTerm tiene su schedule
                "time_slot_id" => 1,
                "classroom_id" => 1,
                "day_id" => 1,
                "start_time" => "06:30",
                "end_time" => "12:30"
            ];
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,
                "time_slot_id" => 1,
                "classroom_id" => 1,
                "day_id" => 3,
                "start_time" => "06:30",
                "end_time" => "12:30"
            ];
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,
                "time_slot_id" => 1,
                "classroom_id" => 1,
                "day_id" => 5,
                "start_time" => "06:30",
                "end_time" => "12:30"
            ];
            
            // Tarde: Lun(1), Mie(3), Vie(5)
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,
                "time_slot_id" => 2,
                "classroom_id" => 1,
                "day_id" => 1,
                "start_time" => "12:30",
                "end_time" => "18:30"
            ];
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,
                "time_slot_id" => 2,
                "classroom_id" => 1,
                "day_id" => 3,
                "start_time" => "12:30",
                "end_time" => "18:30"
            ];
            $sessions[] = [
                "instructor_id" => 16,
                "schedule_id" => $fichaTermId,
                "time_slot_id" => 2,
                "classroom_id" => 1,
                "day_id" => 5,
                "start_time" => "12:30",
                "end_time" => "18:30"
            ];
        }

        foreach ($sessions as $scheduleSession) {
            ScheduleSession::create($scheduleSession);
        }
    }
}
