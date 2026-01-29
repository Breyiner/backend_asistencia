<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            [
                'code' => 'MORNING',
                'name' => 'Mañana',
                'start_time' => '06:30',
                'end_time' => '12:30',
            ],
            [
                'code' => 'AFTERNOON',
                'name' => 'Tarde',
                'start_time' => '12:30',
                'end_time' => '18:30',
            ],
            [
                'code' => 'NIGHT',
                'name' => 'Noche',
                'start_time' => '18:30',
                'end_time' => '22:00',
            ],
        ];

        foreach ($slots as $slot) {
            TimeSlot::create($slot);
        }
    }
}
