<?php

namespace Database\Seeders;

use App\Models\ClassType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classTypes = [
            [
                'name' => 'normal',
                'description' => 'Clase normal programada',
            ],
            [
                'name' => 'adelantada',
                'description' => 'Clase adelantada por programación especial',
            ],
            [
                'name' => 'recuperacion',
                'description' => 'Clase de recuperación',
            ],
        ];

        foreach ($classTypes as $classType) {
            ClassType::create($classType);
        }
    }
}
