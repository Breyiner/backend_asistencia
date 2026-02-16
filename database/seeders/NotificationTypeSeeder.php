<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Recurso creado', 'key' => 'resource_created'],
            ['name' => 'Recurso actualizado', 'key' => 'resource_updated'],
            ['name' => 'Recurso eliminado', 'key' => 'resource_deleted'],

            ['name' => 'Alerta de deserción (límite alcanzado)', 'key' => 'dropout_limit_reached'],
            ['name' => 'Cambio de tipo de documento', 'key' => 'document_type_changed'],

            ['name' => 'Aprendiz cumple mayoría de edad', 'key' => 'apprentice_became_adult'],
        ];

        foreach ($rows as $row) {
            NotificationType::create($row);
        }
    }
}