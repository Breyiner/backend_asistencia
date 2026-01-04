<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [

                'first_name' => 'Admin',
                'last_name' => 'User',
                'document_type_id' => 1,
                'document_number' => '123456789',
                'telephone_number' => '3010000000',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'status_id' => 1,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Gestor',
                'last_name' => 'User',
                'document_type_id' => 1,
                'document_number' => '987654321',
                'telephone_number' => '3010000001',
                'email' => 'gestor@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status_id' => 1,
                'email_verified_at' => now()
            ],
            [
                'first_name' => 'Instructor',
                'last_name' => 'User',
                'document_type_id' => 1,
                'document_number' => '555555555',
                'telephone_number' => '3010000002',
                'email' => 'instructor@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status_id' => 1,
                'email_verified_at' => now()
            ],
        ];

        foreach ($users as $userData) {
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

            $user->roles()->attach($userData['role_id']);
        }
    }
}
