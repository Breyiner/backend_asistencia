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
            // 1. **AUTORIZACIÓN** (Spatie Laravel Permission)
            RoleSeeder::class,                    // Roles base (INSTRUCTOR, APRENDIZ, etc.)
            PermissionSeeder::class,              // Permisos granulares
            RolePermissionSeeder::class,          // Asignación rol-permisos

            // 2. **CATÁLOGOS USUARIOS**
            UserStatusSeeder::class,              // Estados usuario (activo, inactivo)
            DocumentTypeSeeder::class,            // Tipos documento (CC, TI, etc.)

            // 3. **ESTRUCTURA ORGANIZATIVA**
            AreaSeeder::class,                    // Áreas SENA (Informática, Mecánica, etc.)
            
            // 4. **USUARIOS PRINCIPALES**
            UserSeeder::class,                    // Administradores, coordinadores, instructores

            // 5. **PROGRAMACIÓN ACADÉMICA**
            QualificationLevelSeeder::class,      // Niveles (Técnico, Tecnólogo)
            TrainingProgramSeeder::class,         // Programas de formación (ADSO, etc.)
            ShiftSeeder::class,                   // Jornadas (diurna, nocturna)

            // 6. **FICHAS Y ESTADOS**
            FichaStatusSeeder::class,             // Estados ficha (activa, cerrada, etc.)
            FichaSeeder::class,                   // Fichas SENA 

            // 7. **RELACIÓN FICHA-APRENDIZ**
            ApprenticeSeeder::class,              // Matriculación aprendices en fichas

            // 8. **HORARIOS ACADÉMICOS**
            TermSeeder::class,                    // Trimestres (1,2,3,4)
            PhaseSeeder::class,                   // Fases por trimestre
            FichaTermSeeder::class,               // Ficha + Trimestre + Fase
            ScheduleSeeder::class,                // Horarios base por ficha-term
            DaySeeder::class,                     // Días semana (Lunes-Domingo)
            TimeSlotSeeder::class,                // Franjas horarias (08:00-10:00)
            ClassroomSeeder::class,               // Ambientes
            ScheduleSessionSeeder::class,         // Sesiones específicas del horario

            // 9. **EJECUCIÓN CLASES**
            ClassTypeSeeder::class,               // Normal, adelantada, recuperación   
            NoClassReasonSeeder::class,           // Motivos no clase (festivo, etc.)

            // 10. **CONTROL ASISTENCIA**
            AttendanceStatusSeeder::class,        // Presente, ausente, tardanza
            NoClassDaySeeder::class,              // Días sin clase por ficha

            // 11. **SISTEMA NOTIFICACIONES**
            NotificationTypeSeeder::class,        // Tipos notificación (asistencia, etc.)
        ]);
    }
}
