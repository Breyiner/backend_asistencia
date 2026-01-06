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
            TermSeeder::class,
            PhaseSeeder::class,
            DaySeeder::class,
        ]);
    }
}
