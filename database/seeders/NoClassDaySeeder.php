<?php

namespace Database\Seeders;

use App\Models\NoClassDay;
use Illuminate\Database\Seeder;

class NoClassDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $noClassDays = [
            // ========================================
            // FICHA 1
            // ========================================
            [
                'ficha_id' => 1,
                'reason_id' => 1,
                'date' => '2026-01-01',
                'observations' => 'Año Nuevo',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 1,
                'date' => '2026-03-23',
                'observations' => 'Día de San José',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 1,
                'date' => '2026-04-09',
                'observations' => 'Jueves Santo',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 1,
                'date' => '2026-05-01',
                'observations' => 'Día del Trabajo',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 1,
                'date' => '2026-07-20',
                'observations' => 'Día de la Independencia',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 3,
                'date' => '2026-03-10',
                'observations' => 'Día institucional SENA - jornada de capacitación docente.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 3,
                'date' => '2026-05-15',
                'observations' => 'Feria de emprendimiento SENA regional.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 1,
                'reason_id' => 4,
                'date' => '2026-07-10',
                'observations' => 'Mantenimiento eléctrico programado en todo el edificio.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],

            // ========================================
            // FICHA 2
            // ========================================
            [
                'ficha_id' => 2,
                'reason_id' => 1,
                'date' => '2026-01-01',
                'observations' => 'Año Nuevo',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 1,
                'date' => '2026-04-10',
                'observations' => 'Viernes Santo',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 1,
                'date' => '2026-08-07',
                'observations' => 'Batalla de Boyacá',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 1,
                'date' => '2026-12-25',
                'observations' => 'Navidad',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 2,
                'date' => '2026-06-18',
                'observations' => 'Instructor titular reportó incapacidad médica prolongada.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 3,
                'date' => '2026-09-25',
                'observations' => 'Encuentro nacional de instructores.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
            [
                'ficha_id' => 2,
                'reason_id' => 4,
                'date' => '2026-11-20',
                'observations' => 'Reparación de sistema de aires acondicionados.',
                'created_at' => '2026-02-03 14:30:00',
                'updated_at' => '2026-02-03 14:30:00',
            ],
        ];

        foreach ($noClassDays as $noClassDay) {
            NoClassDay::create($noClassDay);
        }
    }
}