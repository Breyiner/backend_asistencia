<?php

namespace Database\Seeders;

use App\Models\ScheduleSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scheduleSessions = [

            [
                "instructor_id" => 3,
                "schedule_id" => 1,
                "shift_id" => 1,
                "classroom_id" => 1,
                "day_id" => 1,
                "start_time" => "06:30",
                "end_time" => "11:30"
            ],
        ];

        foreach ($scheduleSessions as $scheduleSession) {
            ScheduleSession::create($scheduleSession);
        }
    }
}
