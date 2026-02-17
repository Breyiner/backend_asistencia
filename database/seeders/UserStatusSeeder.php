<?php

namespace Database\Seeders;

use App\Models\UserStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserStatus::create([
            'name' => 'Activo',
            'description' => 'Estado cuando el usuario tiene la cuenta activa.'
        ]);

        UserStatus::create([
            'name' => 'Inactivo',
            'description' => 'Estado cuando el usuario tiene la cuenta inactiva.'
        ]);
    }
}
