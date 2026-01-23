<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'present',         
                'name' => 'Presente',             
                'description' => 'Aprendiz asistió completo a la clase'
            ],
            [
                'code' => 'absent',          
                'name' => 'Ausente',              
                'description' => 'Aprendiz no asistió a la clase'
            ],
            [
                'code' => 'excused_absence', 
                'name' => 'Ausencia Justificada', 
                'description' => 'Ausencia con justificación médica o permiso'
            ],
            [
                'code' => 'late',            
                'name' => 'Tardanza',             
                'description' => 'Aprendiz llegó tarde a la clase'
            ],
            [
                'code' => 'early_exit',      
                'name' => 'Salida Anticipada',    
                'description' => 'Aprendiz salió antes de finalizar la clase'
            ],
            [
                'code' => 'unregistered',    
                'name' => 'Sin Registrar',        
                'description' => 'Estado inicial al crear la clase Real'
            ],
        ];

        foreach ($statuses as $status) {
            AttendanceStatus::create([
                'code' => $status['code'],
                'name' => $status['name'],
                'description' => $status['description']
            ]);
        }
    }
}
