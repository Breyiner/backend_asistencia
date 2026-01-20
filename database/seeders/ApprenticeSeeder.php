<?php

namespace Database\Seeders;

use App\Models\Apprentice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApprenticeSeeder extends Seeder
{
    public function run(): void
    {
        $apprentices = [];
        
        for ($i = 1; $i <= 15; $i++) {
            $apprentices[] = [
                'first_name' => 'Aprendiz ' . $i,
                'last_name' => 'Apellido ' . $i,
                'document_type_id' => 1,
                'document_number' => '900' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'telephone_number' => '30200000' . $i,
                'email' => 'aprendiz' . $i . '@gmail.com',
                'birth_date' => now()->subYears(18 + ($i % 5))->format('Y-m-d'),
                'password' => null,
                'roles' => [4],
                'status_id' => 1,
                'type' => 'apprentice',
                'email_verified_at' => now()
            ];
        }

        foreach ($apprentices as $apprenticeData) {
            $apprentice = Apprentice::create([
                'document_type_id' => $apprenticeData['document_type_id'],
                'document_number' => $apprenticeData['document_number'],
                'email' => $apprenticeData['email'],
                'password' => $apprenticeData['password'],
                'ficha_id' => 1,
                'status_id' => $apprenticeData['status_id'],
                'type' => $apprenticeData['type'],
                'email_verified_at' => $apprenticeData['email_verified_at'],
            ]);

            $profile = $apprentice->profile()->create([
                'first_name' => $apprenticeData['first_name'],
                'last_name' => $apprenticeData['last_name'],
                'telephone_number' => $apprenticeData['telephone_number'],
                'birth_date' => $apprenticeData['birth_date'],
            ]);

            $apprentice->roles()->attach($apprenticeData['roles']);
        }
    }
}