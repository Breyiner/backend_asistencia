<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            ['name' => 'Cédula de Ciudadanía', 'acronym' => 'CC'],
            ['name' => 'Tarjeta de Identidad', 'acronym' => 'TI'],
            ['name' => 'Cédula de Extranjería', 'acronym' => 'CE'],
            ['name' => 'Pasaporte', 'acronym' => 'PA'],
            ['name' => 'Permiso de Permanencia Temporal', 'acronym' => 'PPT'],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::create($type);
        }
    }
}
