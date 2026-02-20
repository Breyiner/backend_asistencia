<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset caché de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::findByName('Administrador');
        $coordinatorRole = Role::findByName('Coordinador');
        $gestorRole = Role::findByName('Gestor de Fichas');
        $instructorRole = Role::findByName('Instructor');

        $allPermissions = Permission::all();

        // Admin: todos los permisos
        $adminRole->syncPermissions($allPermissions);

        // Coordinador: vista + CRUD limitado (sin delete globales)
        $coordinatorPermissions = [
            // Catálogos base
            'user_statuses.viewAny',
            'areas.viewAny',
            'qualification_levels.viewAny',
            'ficha_statuses.viewAny',
            'phases.viewAny',
            'days.viewAny',
            'shifts.viewAny',
            'time_slots.viewAny',
            'document_types.viewAny',
            'attendance_statuses.viewAny',
            'no_class_reasons.viewAny',

            // Aprendices (solo ver)
            'apprentices.viewAny',
            'apprentices.view',

            // Clases reales (managed + view)
            'real_classes.viewManaged',
            'real_classes.view',

            // Asistencias (solo ver/export)
            'attendances.viewAny',
            'attendances.view',
            'attendances.monthlyRegister',
            'attendances.byClassRealId',
            'attendances.export',

            // Programas (CRUD completo)
            'training_programs.viewAny',
            'training_programs.view',
            'training_programs.create',
            'training_programs.update',
            'training_programs.delete',

            // Fichas (CRUD, scopes en Policy)
            'fichas.viewAny',
            'fichas.view',
            'fichas.availableForRealClass',
            'fichas.create',
            'fichas.update',
            // 'fichas.delete' REMOVIDO - scopes en Policy

            // Trimestres Ficha (CRUD)
            'ficha_terms.viewAny',
            'ficha_terms.create',
            'ficha_terms.setCurrent',
            'ficha_terms.update',
            'ficha_terms.delete',

            // Ambientes (solo ver)
            'classrooms.viewAny',
            'classrooms.view',

            // Horarios (CRUD)
            'schedules.view',
            'schedules.create',
            'schedules.update',
            'schedules.delete',

            // Sesiones de Horario
            'schedule_sessions.byFichaId',
            'schedule_sessions.create',
            'schedule_sessions.update',
            'schedule_sessions.delete',

            // Notificaciones
            'notifications.viewAny',
            'notifications.markAsRead',
            'notifications.markAllAsRead',

            // Perfil propio
            'profiles_users.viewOwn',
            'profiles_users.updateOwn',

            // Días sin clase
            'no_class_days.viewAny',
            'no_class_days.view',
            'no_class_days.check',
        ];
        $coordinatorRole->syncPermissions($coordinatorPermissions);

        // Gestor: CRUD fichas/aprendices + clases managed (explícito)
        $gestorPermissions = [
            // Catálogos base
            'user_statuses.viewAny',
            'areas.viewAny',
            'qualification_levels.viewAny',
            'ficha_statuses.viewAny',
            'phases.viewAny',
            'days.viewAny',
            'shifts.viewAny',
            'time_slots.viewAny',
            'document_types.viewAny',
            'attendance_statuses.viewAny',
            'no_class_reasons.viewAny',

            // Aprendices (CRUD + import)
            'apprentices.viewAny',
            'apprentices.view',
            'apprentices.import',
            'apprentices.create',
            'apprentices.update',
            'apprentices.delete',

            // Clases reales (managed + CRUD)
            'real_classes.viewManaged',
            'real_classes.view',
            'real_classes.create',
            'real_classes.update',
            'real_classes.delete',

            // Asistencias (ver + update/export)
            'attendances.viewAny',
            'attendances.view',
            'attendances.monthlyRegister',
            'attendances.byClassRealId',
            'attendances.export',
            'attendances.update',

            // Programas (CRUD)
            'training_programs.viewAny',
            'training_programs.view',
            'training_programs.create',
            'training_programs.update',
            'training_programs.delete',

            // Fichas (CRUD)
            'fichas.viewAny',
            'fichas.view',
            'fichas.availableForRealClass',
            'fichas.create',
            'fichas.update',
            'fichas.delete',

            // Trimestres Ficha (CRUD)
            'ficha_terms.viewAny',
            'ficha_terms.create',
            'ficha_terms.setCurrent',
            'ficha_terms.update',
            'ficha_terms.delete',

            // Ambientes (ver)
            'classrooms.viewAny',
            'classrooms.view',

            // Horarios (CRUD)
            'schedules.view',
            'schedules.create',
            'schedules.update',
            'schedules.delete',

            // Sesiones de Horario
            'schedule_sessions.byFichaId',
            'schedule_sessions.create',
            'schedule_sessions.update',
            'schedule_sessions.delete',

            // Notificaciones + Perfil + Días sin clase (igual)
            'notifications.viewAny',
            'notifications.markAsRead',
            'notifications.markAllAsRead',
            'profiles_users.viewOwn',
            'profiles_users.updateOwn',
            'no_class_days.viewAny',
            'no_class_days.view',
            'no_class_days.check',
        ];
        $gestorRole->syncPermissions($gestorPermissions);

        // Instructor: own/managed + asistencias (mínimo viable)
        $instructorPermissions = [
            // Catálogos mínimos para clases
            'user_statuses.viewAny',
            'areas.viewAny',
            'qualification_levels.viewAny',
            'ficha_statuses.viewAny',
            'phases.viewAny',
            'days.viewAny',
            'shifts.viewAny',
            'time_slots.viewAny',
            'document_types.viewAny',
            'attendance_statuses.viewAny',
            'no_class_reasons.viewAny',
            'classrooms.viewAny',

            // Aprendices (solo ver)
            'apprentices.viewAny',
            'apprentices.view',

            // Clases reales (own + CRUD)
            'real_classes.viewOwn',
            'real_classes.view',
            'real_classes.create',
            'real_classes.update',
            'real_classes.delete',

            // Asistencias (CRUD)
            'attendances.viewAny',
            'attendances.view',
            'attendances.monthlyRegister',
            'attendances.byClassRealId',
            'attendances.update',

            // Fichas (solo disponibles para clases)
            'fichas.viewAny',
            'fichas.availableForRealClass',

            // Notificaciones + Perfil + Días sin clase
            'notifications.viewAny',
            'notifications.markAsRead',
            'notifications.markAllAsRead',
            'profiles_users.viewOwn',
            'profiles_users.updateOwn',
            'no_class_days.viewAny',
            'no_class_days.view',
            'no_class_days.check',
        ];
        $instructorRole->syncPermissions($instructorPermissions);
    }
}