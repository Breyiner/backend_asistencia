<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder principal **Sistema Académico SENA**.
 * 
 * **Orden de ejecución crítico** para respetar dependencias de claves foráneas:
 * 1. Roles/Permisos → 2. Usuarios → 3. Programas/Áreas → 4. Fichas/Turnos → 5. Horarios → 6. Asistencia → 7. Notificaciones
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * Ejecuta seeders en orden jerárquico para evitar violaciones de FK.
     */
    public function run(): void
    {
        $this->call([
            // **AUTORIZACIÓN** (Spatie Laravel Permission)
            RoleSeeder::class,                    // Roles base (INSTRUCTOR, APRENDIZ, etc.)
            PermissionSeeder::class,              // Permisos granulares
            RolePermissionSeeder::class,          // Asignación rol-permisos

            // **CATÁLOGOS USUARIOS**
            UserStatusSeeder::class,              // Estados usuario (activo, inactivo)
            DocumentTypeSeeder::class,            // Tipos documento (CC, TI, etc.)

            // **ESTRUCTURA ORGANIZATIVA**
            AreaSeeder::class,                    // Áreas SENA (Informática, Mecánica, etc.)
            
            // **USUARIOS PRINCIPALES**
            UserSeeder::class,                    // Administradores, scanner

            // **PROGRAMACIÓN ACADÉMICA**
            QualificationLevelSeeder::class,      // Niveles (Técnico, Tecnólogo)
            TrainingProgramSeeder::class,         // Programas de formación (ADSO, etc.)
            ShiftSeeder::class,                   // Jornadas (diurna, nocturna)

            // **FICHAS Y ESTADOS**
            FichaStatusSeeder::class,             // Estados ficha (activa, cerrada, etc.)
            FichaSeeder::class,                   // Fichas SENA 

            // **HORARIOS ACADÉMICOS**
            TermSeeder::class,                    // Trimestres (1,2,3,4)
            PhaseSeeder::class,                   // Fases por trimestre
            DaySeeder::class,                     // Días semana (Lunes-Domingo)
            TimeSlotSeeder::class,                // Franjas horarias (08:00-10:00)
            ClassroomSeeder::class,               // Ambientes

            // **EJECUCIÓN CLASES**
            ClassTypeSeeder::class,               // Normal, adelantada, recuperación   
            NoClassReasonSeeder::class,           // Motivos no clase (festivo, etc.)

            // **CONTROL ASISTENCIA**
            AttendanceStatusSeeder::class,        // Presente, ausente, tardanza

            // **SISTEMA NOTIFICACIONES**
            NotificationTypeSeeder::class,        // Tipos notificación (asistencia, etc.)
        ]);
    }
}
