<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $allAreaIds = Area::query()->pluck('id')->toArray();

        $adminRoleId       = Role::idByCode('ADMIN');
        $coordinatorRoleId = Role::idByCode('COORDINADOR');
        $gestorRoleId      = Role::idByCode('GESTOR_FICHAS');
        $instructorRoleId  = Role::idByCode('INSTRUCTOR');
        $scannerRoleId     = Role::idByCode('SCANNER');

        $admin = User::create([
            'document_type_id' => 1,
            'document_number' => '0000000000',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password.123'),
            'status_id' => 1,
            'email_verified_at' => now(),
        ]);

        $admin->profile()->create([
            'first_name' => 'Enzy Zulay',
            'last_name' => 'Angarita Bermudez',
            'telephone_number' => '0000000000',
        ]);

        $admin->roles()->syncWithoutDetaching([
            $adminRoleId,
            $gestorRoleId,
            $instructorRoleId,
            $coordinatorRoleId,
        ]);

        $admin->areas()->sync($allAreaIds);

        $scanner = User::create([
            'document_type_id' => 1,
            'document_number' => '1111111111',
            'email' => 'scanner@gmail.com',
            'password' => Hash::make('Password.123'),
            'status_id' => 1,
            'email_verified_at' => now(),
        ]);

        $scanner->profile()->create([
            'first_name' => 'Escáner',
            'last_name' => 'User',
            'telephone_number' => '1111111111',
        ]);

        $scanner->roles()->syncWithoutDetaching([$scannerRoleId]);
    }
}