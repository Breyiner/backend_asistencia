<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserStatusSeeder::class,
            DocumentTypeSeeder::class,
            UserSeeder::class,
            AreaSeeder::class,
            QualificationLevelSeeder::class,
            TrainingProgramSeeder::class,
            FichaStatusSeeder::class,
            FichaSeeder::class,
            ApprenticeSeeder::class,
            TermSeeder::class,
            PhaseSeeder::class,
            FichaTermSeeder::class,
            ScheduleSeeder::class,
            DaySeeder::class,
            ShiftSeeder::class,
            TimeSlotSeeder::class,
            ClassroomSeeder::class,
            ScheduleSessionSeeder::class,
            ClassTypeSeeder::class,
            AttendanceStatusSeeder::class,
            NotificationTypeSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
