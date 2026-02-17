<?php

namespace Database\Seeders;

use App\Models\Apprentice;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApprenticeSeeder extends Seeder
{
    public function run(): void
    {
        $apprenticeRoleId = Role::idByCode('APRENDIZ');

        for ($i = 1; $i <= 15; $i++) {
            $apprentice = Apprentice::create([
                'document_type_id'    => 1,
                'document_number'     => '900' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'email'               => 'aprendiz' . $i . '@gmail.com',
                'password'            => null,
                'ficha_id'            => 1,
                'status_id'           => 1,
                'type'                => 'apprentice',
                'email_verified_at'   => now(),
            ]);

            $apprentice->profile()->create([
                'first_name'       => 'Aprendiz ' . $i,
                'last_name'        => 'Apellido ' . $i,
                'telephone_number' => '30200000' . $i,
                'birth_date'       => now()->subYears(18 + ($i % 5))->format('Y-m-d'),
            ]);

            $apprentice->roles()->syncWithoutDetaching([$apprenticeRoleId]);
        }
    }
}