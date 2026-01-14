<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [];
        
        // Generar 15 instructores únicos
        for ($i = 1; $i <= 15; $i++) {
            $instructors[] = [
                'first_name' => 'Instructor ' . $i,
                'last_name' => 'Apellido ' . $i,
                'document_type_id' => 1,
                'document_number' => '800' . str_pad($i, 6, '0', STR_PAD_LEFT),  // 800000001 a 800000015
                'telephone_number' => '30100000' . $i,
                'email' => 'instructor' . $i . '@gmail.com',
                'password' => Hash::make('Password.123'),
                'roles' => [3],  // Solo rol instructor (ID 3)
                'status_id' => 1,
                'email_verified_at' => now()
            ];
        }

        // Admin (rol 1,2,3)
        $instructors[] = [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'document_type_id' => 1,
            'document_number' => '123456789',
            'telephone_number' => '3010000000',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password.123'),
            'roles' => [1,2,3],
            'status_id' => 1,
            'email_verified_at' => now()
        ];

        // Gestor (rol 2)
        $instructors[] = [
            'first_name' => 'Gestor',
            'last_name' => 'User',
            'document_type_id' => 1,
            'document_number' => '987654321',
            'telephone_number' => '3010000001',
            'email' => 'gestor@gmail.com',
            'password' => Hash::make('Password.123'),
            'roles' => [2],
            'status_id' => 1,
            'email_verified_at' => now()
        ];

        foreach ($instructors as $userData) {
            $user = User::create([
                'document_type_id' => $userData['document_type_id'],
                'document_number' => $userData['document_number'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'status_id' => $userData['status_id'],
                'email_verified_at' => $userData['email_verified_at'],
            ]);

            $profile = $user->profile()->create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'telephone_number' => $userData['telephone_number'],
            ]);

            $user->roles()->attach($userData['roles']);
        }
    }
}