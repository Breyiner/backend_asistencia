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

        $areaId1 = $allAreaIds[0] ?? null;
        $areaId2 = $allAreaIds[1] ?? null;
        $areaId3 = $allAreaIds[2] ?? null;
        $areaId4 = $allAreaIds[3] ?? null;
        $areaId5 = $allAreaIds[4] ?? null;

        for ($i = 1; $i <= 15; $i++) {
            $user = User::create([
                'document_type_id' => 1,
                'document_number' => '800' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'email' => 'instructor' . $i . '@gmail.com',
                'password' => Hash::make('Password.123'),
                'status_id' => 1,
                'email_verified_at' => now(),
            ]);

            $user->profile()->create([
                'first_name' => 'Instructor ' . $i,
                'last_name' => 'Apellido ' . $i,
                'telephone_number' => '30100000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);

            $user->roles()->syncWithoutDetaching([$instructorRoleId]);

            $areaIds = match ($i % 3) {
                0 => array_values(array_filter([$areaId4])),
                1 => array_values(array_filter([$areaId1])),
                default => array_values(array_filter([$areaId3])),
            };

            $user->areas()->sync($areaIds);
        }

        $admin = User::create([
            'document_type_id' => 1,
            'document_number' => '123456789',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password.123'),
            'status_id' => 1,
            'email_verified_at' => now(),
        ]);

        $admin->profile()->create([
            'first_name' => 'Enzy Zulay',
            'last_name' => 'Angarita Bermudez',
            'telephone_number' => '3010000000',
        ]);

        $admin->roles()->syncWithoutDetaching([
            $adminRoleId,
            $gestorRoleId,
            $instructorRoleId,
            $coordinatorRoleId,
        ]);

        $admin->areas()->sync($allAreaIds);

        $coordinator = User::create([
            'document_type_id' => 1,
            'document_number' => '1122334455',
            'email' => 'coordinador@gmail.com',
            'password' => Hash::make('Password.123'),
            'status_id' => 1,
            'email_verified_at' => now(),
        ]);

        $coordinator->profile()->create([
            'first_name' => 'Coordinador',
            'last_name' => 'User',
            'telephone_number' => '3010000002',
        ]);

        $coordinator->roles()->syncWithoutDetaching([$coordinatorRoleId]);
        $coordinator->areas()->sync($allAreaIds);

        $gestor = User::create([
            'document_type_id' => 1,
            'document_number' => '987654321',
            'email' => 'gestor@gmail.com',
            'password' => Hash::make('Password.123'),
            'status_id' => 1,
            'email_verified_at' => now(),
        ]);

        $gestor->profile()->create([
            'first_name' => 'Gestor',
            'last_name' => 'User',
            'telephone_number' => '3010000001',
        ]);

        $gestor->roles()->syncWithoutDetaching([$gestorRoleId]);

        $gestorAreaIds = array_values(array_filter([$areaId2, $areaId5]));
        $gestor->areas()->sync($gestorAreaIds);

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
            'telephone_number' => '3010000003',
        ]);

        $scanner->roles()->syncWithoutDetaching([$scannerRoleId]);
    }
}