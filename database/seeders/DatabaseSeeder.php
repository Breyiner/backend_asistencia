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
            AreaSeeder::class,
            UserSeeder::class,
            QualificationLevelSeeder::class,
            TrainingProgramSeeder::class,
            ShiftSeeder::class,
            FichaStatusSeeder::class,
            FichaSeeder::class,
            ApprenticeSeeder::class,
            TermSeeder::class,
            PhaseSeeder::class,
            FichaTermSeeder::class,
            ScheduleSeeder::class,
            DaySeeder::class,
            TimeSlotSeeder::class,
            ClassroomSeeder::class,
            ScheduleSessionSeeder::class,
            ClassTypeSeeder::class,
            AttendanceStatusSeeder::class,
            NotificationTypeSeeder::class,
            NoClassReasonSeeder::class,
            NoClassDaySeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
