<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Recurso creado', 'key' => 'resource_created'],
            ['name' => 'Recurso actualizado', 'key' => 'resource_updated'],
            ['name' => 'Recurso eliminado', 'key' => 'resource_deleted'],

            ['name' => 'Alerta de deserción (límite alcanzado)', 'key' => 'dropout_limit_reached'],
            ['name' => 'Cambio de tipo de documento', 'key' => 'document_type_changed'],
        ];

        foreach ($rows as $row) {
            NotificationType::create($row);
        }
    }
}
