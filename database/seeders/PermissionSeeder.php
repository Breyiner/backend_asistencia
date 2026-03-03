<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Roles
            ['name' => 'roles.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar roles', 'group' => 'Roles'],
            ['name' => 'roles.view', 'guard_name' => 'web', 'display_name' => 'Ver rol', 'group' => 'Roles'],
            ['name' => 'roles.create', 'guard_name' => 'web', 'display_name' => 'Crear rol', 'group' => 'Roles'],
            ['name' => 'roles.update', 'guard_name' => 'web', 'display_name' => 'Editar rol', 'group' => 'Roles'],
            ['name' => 'roles.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar rol', 'group' => 'Roles'],

            // Estados de Usuario
            ['name' => 'user_statuses.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar estados de usuario', 'group' => 'Estados Usuario'],
            ['name' => 'user_statuses.view', 'guard_name' => 'web', 'display_name' => 'Ver estado de usuario', 'group' => 'Estados Usuario'],
            ['name' => 'user_statuses.create', 'guard_name' => 'web', 'display_name' => 'Crear estado de usuario', 'group' => 'Estados Usuario'],
            ['name' => 'user_statuses.update', 'guard_name' => 'web', 'display_name' => 'Editar estado de usuario', 'group' => 'Estados Usuario'],
            ['name' => 'user_statuses.partialUpdate', 'guard_name' => 'web', 'display_name' => 'Actualización parcial estado', 'group' => 'Estados Usuario'],
            ['name' => 'user_statuses.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar estado de usuario', 'group' => 'Estados Usuario'],

            // Tipos de Documento
            ['name' => 'document_types.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar tipos de documento', 'group' => 'Tipos Documento'],
            ['name' => 'document_types.view', 'guard_name' => 'web', 'display_name' => 'Ver tipo de documento', 'group' => 'Tipos Documento'],
            ['name' => 'document_types.create', 'guard_name' => 'web', 'display_name' => 'Crear tipo de documento', 'group' => 'Tipos Documento'],
            ['name' => 'document_types.update', 'guard_name' => 'web', 'display_name' => 'Editar tipo de documento', 'group' => 'Tipos Documento'],
            ['name' => 'document_types.partialUpdate', 'guard_name' => 'web', 'display_name' => 'Actualización parcial documento', 'group' => 'Tipos Documento'],
            ['name' => 'document_types.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar tipo de documento', 'group' => 'Tipos Documento'],

            // Usuarios
            ['name' => 'users.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar usuarios', 'group' => 'Usuarios'],
            ['name' => 'users.viewOwn', 'guard_name' => 'web', 'display_name' => 'Ver propio usuario', 'group' => 'Usuarios'],
            ['name' => 'users.view', 'guard_name' => 'web', 'display_name' => 'Ver usuario', 'group' => 'Usuarios'],
            ['name' => 'users.create', 'guard_name' => 'web', 'display_name' => 'Crear usuario', 'group' => 'Usuarios'],
            ['name' => 'users.update', 'guard_name' => 'web', 'display_name' => 'Editar usuario', 'group' => 'Usuarios'],
            ['name' => 'users.updateRoles', 'guard_name' => 'web', 'display_name' => 'Actualizar roles usuario', 'group' => 'Usuarios'],
            ['name' => 'users.updateOwnPassword', 'guard_name' => 'web', 'display_name' => 'Cambiar propia contraseña', 'group' => 'Usuarios'],
            ['name' => 'users.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar usuario', 'group' => 'Usuarios'],

            // Perfiles de Usuario
            ['name' => 'profiles_users.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar perfiles', 'group' => 'Perfiles Usuarios'],
            ['name' => 'profiles_users.viewOwn', 'guard_name' => 'web', 'display_name' => 'Ver propio perfil', 'group' => 'Perfiles Usuarios'],
            ['name' => 'profiles_users.view', 'guard_name' => 'web', 'display_name' => 'Ver perfil de usuario', 'group' => 'Perfiles Usuarios'],
            ['name' => 'profiles_users.viewByUser', 'guard_name' => 'web', 'display_name' => 'Ver perfil por usuario', 'group' => 'Perfiles Usuarios'],
            ['name' => 'profiles_users.updateOwn', 'guard_name' => 'web', 'display_name' => 'Editar propio perfil', 'group' => 'Perfiles Usuarios'],
            ['name' => 'profiles_users.update', 'guard_name' => 'web', 'display_name' => 'Editar perfil de usuario', 'group' => 'Perfiles Usuarios'],

            // Áreas e Institución
            ['name' => 'areas.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar áreas', 'group' => 'Áreas'],
            ['name' => 'areas.view', 'guard_name' => 'web', 'display_name' => 'Ver área', 'group' => 'Áreas'],
            ['name' => 'areas.create', 'guard_name' => 'web', 'display_name' => 'Crear área', 'group' => 'Áreas'],
            ['name' => 'areas.update', 'guard_name' => 'web', 'display_name' => 'Editar área', 'group' => 'Áreas'],
            ['name' => 'areas.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar área', 'group' => 'Áreas'],

            ['name' => 'qualification_levels.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar niveles Formación', 'group' => 'Niveles Formación'],
            ['name' => 'qualification_levels.view', 'guard_name' => 'web', 'display_name' => 'Ver nivel Formación', 'group' => 'Niveles Formación'],
            ['name' => 'qualification_levels.create', 'guard_name' => 'web', 'display_name' => 'Crear nivel Formación', 'group' => 'Niveles Formación'],
            ['name' => 'qualification_levels.update', 'guard_name' => 'web', 'display_name' => 'Editar nivel Formación', 'group' => 'Niveles Formación'],
            ['name' => 'qualification_levels.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar nivel Formación', 'group' => 'Niveles Formación'],

            ['name' => 'training_programs.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar programas formación', 'group' => 'Programas Formación'],
            ['name' => 'training_programs.view', 'guard_name' => 'web', 'display_name' => 'Ver programa formación', 'group' => 'Programas Formación'],
            ['name' => 'training_programs.create', 'guard_name' => 'web', 'display_name' => 'Crear programa formación', 'group' => 'Programas Formación'],
            ['name' => 'training_programs.update', 'guard_name' => 'web', 'display_name' => 'Editar programa formación', 'group' => 'Programas Formación'],
            ['name' => 'training_programs.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar programa formación', 'group' => 'Programas Formación'],

            // Fichas y Aprendices
            ['name' => 'ficha_statuses.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar estados ficha', 'group' => 'Estados Ficha'],
            ['name' => 'ficha_statuses.view', 'guard_name' => 'web', 'display_name' => 'Ver estado ficha', 'group' => 'Estados Ficha'],
            ['name' => 'ficha_statuses.create', 'guard_name' => 'web', 'display_name' => 'Crear estado ficha', 'group' => 'Estados Ficha'],
            ['name' => 'ficha_statuses.update', 'guard_name' => 'web', 'display_name' => 'Editar estado ficha', 'group' => 'Estados Ficha'],
            ['name' => 'ficha_statuses.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar estado ficha', 'group' => 'Estados Ficha'],

            ['name' => 'fichas.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar fichas', 'group' => 'Fichas'],
            ['name' => 'fichas.view', 'guard_name' => 'web', 'display_name' => 'Ver ficha', 'group' => 'Fichas'],
            ['name' => 'fichas.availableForRealClass', 'guard_name' => 'web', 'display_name' => 'Listar fichas disponibles para registrar clase real', 'group' => 'Fichas'],
            ['name' => 'fichas.create', 'guard_name' => 'web', 'display_name' => 'Crear ficha', 'group' => 'Fichas'],
            ['name' => 'fichas.update', 'guard_name' => 'web', 'display_name' => 'Editar ficha', 'group' => 'Fichas'],
            ['name' => 'fichas.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar ficha', 'group' => 'Fichas'],

            ['name' => 'apprentices.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar aprendices', 'group' => 'Aprendices'],
            ['name' => 'apprentices.view', 'guard_name' => 'web', 'display_name' => 'Ver aprendiz', 'group' => 'Aprendices'],
            ['name' => 'apprentices.create', 'guard_name' => 'web', 'display_name' => 'Crear aprendiz', 'group' => 'Aprendices'],
            ['name' => 'apprentices.update', 'guard_name' => 'web', 'display_name' => 'Editar aprendiz', 'group' => 'Aprendices'],
            ['name' => 'apprentices.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar aprendiz', 'group' => 'Aprendices'],
            ['name' => 'apprentices.import', 'guard_name' => 'web', 'display_name' => 'Importar aprendices', 'group' => 'Aprendices'],

            // Planeación (Trimestres, Fases, Horarios)
            ['name' => 'terms.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar Trimestres', 'group' => 'Trimestres'],
            ['name' => 'terms.view', 'guard_name' => 'web', 'display_name' => 'Ver Trimestre', 'group' => 'Trimestres'],
            ['name' => 'terms.create', 'guard_name' => 'web', 'display_name' => 'Crear Trimestre', 'group' => 'Trimestres'],
            ['name' => 'terms.update', 'guard_name' => 'web', 'display_name' => 'Editar Trimestre', 'group' => 'Trimestres'],
            ['name' => 'terms.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar Trimestre', 'group' => 'Trimestres'],

            ['name' => 'phases.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar fases', 'group' => 'Fases'],
            ['name' => 'phases.view', 'guard_name' => 'web', 'display_name' => 'Ver fase', 'group' => 'Fases'],
            ['name' => 'phases.create', 'guard_name' => 'web', 'display_name' => 'Crear fase', 'group' => 'Fases'],
            ['name' => 'phases.update', 'guard_name' => 'web', 'display_name' => 'Editar fase', 'group' => 'Fases'],
            ['name' => 'phases.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar fase', 'group' => 'Fases'],

            ['name' => 'ficha_terms.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar Trimestres ficha', 'group' => 'Trimestres Ficha'],
            ['name' => 'ficha_terms.view', 'guard_name' => 'web', 'display_name' => 'Ver Trimestre ficha', 'group' => 'Trimestres Ficha'],
            ['name' => 'ficha_terms.create', 'guard_name' => 'web', 'display_name' => 'Crear Trimestre ficha', 'group' => 'Trimestres Ficha'],
            ['name' => 'ficha_terms.update', 'guard_name' => 'web', 'display_name' => 'Editar Trimestre ficha', 'group' => 'Trimestres Ficha'],
            ['name' => 'ficha_terms.setCurrent', 'guard_name' => 'web', 'display_name' => 'Establecer Trimestre actual', 'group' => 'Trimestres Ficha'],
            ['name' => 'ficha_terms.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar Trimestre ficha', 'group' => 'Trimestres Ficha'],

            ['name' => 'schedules.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar horarios', 'group' => 'Horarios'],
            ['name' => 'schedules.view', 'guard_name' => 'web', 'display_name' => 'Ver horario', 'group' => 'Horarios'],
            ['name' => 'schedules.create', 'guard_name' => 'web', 'display_name' => 'Crear horario', 'group' => 'Horarios'],
            ['name' => 'schedules.update', 'guard_name' => 'web', 'display_name' => 'Editar horario', 'group' => 'Horarios'],
            ['name' => 'schedules.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar horario', 'group' => 'Horarios'],

            // Infraestructura de Horarios
            ['name' => 'days.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar días', 'group' => 'Días'],
            ['name' => 'days.view', 'guard_name' => 'web', 'display_name' => 'Ver día', 'group' => 'Días'],
            ['name' => 'days.create', 'guard_name' => 'web', 'display_name' => 'Crear día', 'group' => 'Días'],
            ['name' => 'days.update', 'guard_name' => 'web', 'display_name' => 'Editar día', 'group' => 'Días'],
            ['name' => 'days.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar día', 'group' => 'Días'],

            ['name' => 'shifts.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar Jornadas', 'group' => 'Jornadas'],
            ['name' => 'shifts.view', 'guard_name' => 'web', 'display_name' => 'Ver Jornada', 'group' => 'Jornadas'],
            ['name' => 'shifts.create', 'guard_name' => 'web', 'display_name' => 'Crear Jornada', 'group' => 'Jornadas'],
            ['name' => 'shifts.update', 'guard_name' => 'web', 'display_name' => 'Editar Jornada', 'group' => 'Jornadas'],
            ['name' => 'shifts.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar Jornada', 'group' => 'Jornadas'],

            ['name' => 'time_slots.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar franjas horarias', 'group' => 'Franjas Horarias'],
            ['name' => 'time_slots.view',    'guard_name' => 'web', 'display_name' => 'Ver franja horaria',     'group' => 'Franjas Horarias'],
            ['name' => 'time_slots.create',  'guard_name' => 'web', 'display_name' => 'Crear franja horaria',   'group' => 'Franjas Horarias'],
            ['name' => 'time_slots.update',  'guard_name' => 'web', 'display_name' => 'Editar franja horaria',  'group' => 'Franjas Horarias'],
            ['name' => 'time_slots.delete',  'guard_name' => 'web', 'display_name' => 'Eliminar franja horaria', 'group' => 'Franjas Horarias'],

            ['name' => 'classrooms.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar Ambientes', 'group' => 'Ambientes'],
            ['name' => 'classrooms.view', 'guard_name' => 'web', 'display_name' => 'Ver Ambiente', 'group' => 'Ambientes'],
            ['name' => 'classrooms.create', 'guard_name' => 'web', 'display_name' => 'Crear Ambiente', 'group' => 'Ambientes'],
            ['name' => 'classrooms.update', 'guard_name' => 'web', 'display_name' => 'Editar Ambiente', 'group' => 'Ambientes'],
            ['name' => 'classrooms.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar Ambiente', 'group' => 'Ambientes'],

            ['name' => 'class_types.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar tipos clase', 'group' => 'Tipos Clase'],
            ['name' => 'class_types.view', 'guard_name' => 'web', 'display_name' => 'Ver tipo clase', 'group' => 'Tipos Clase'],
            ['name' => 'class_types.create', 'guard_name' => 'web', 'display_name' => 'Crear tipo clase', 'group' => 'Tipos Clase'],
            ['name' => 'class_types.update', 'guard_name' => 'web', 'display_name' => 'Editar tipo clase', 'group' => 'Tipos Clase'],
            ['name' => 'class_types.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar tipo clase', 'group' => 'Tipos Clase'],

            // Ejecución y Asistencia
            ['name' => 'schedule_sessions.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar sesiones horario', 'group' => 'Sesiones Horario'],
            ['name' => 'schedule_sessions.byFichaId', 'guard_name' => 'web', 'display_name' => 'Ver sesiones del horario actual por ficha', 'group' => 'Sesiones Horario'],
            ['name' => 'schedule_sessions.view', 'guard_name' => 'web', 'display_name' => 'Ver sesión horario', 'group' => 'Sesiones Horario'],
            ['name' => 'schedule_sessions.create', 'guard_name' => 'web', 'display_name' => 'Crear sesión horario', 'group' => 'Sesiones Horario'],
            ['name' => 'schedule_sessions.update', 'guard_name' => 'web', 'display_name' => 'Editar sesión horario', 'group' => 'Sesiones Horario'],
            ['name' => 'schedule_sessions.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar sesión horario', 'group' => 'Sesiones Horario'],

            ['name' => 'real_classes.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar clases reales', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.viewOwn', 'guard_name' => 'web', 'display_name' => 'Listar mis clases reales', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.viewManaged', 'guard_name' => 'web', 'display_name' => 'Listar clases reales de mis fichas', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.view', 'guard_name' => 'web', 'display_name' => 'Ver clase real', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.create', 'guard_name' => 'web', 'display_name' => 'Registrar clase real', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.update', 'guard_name' => 'web', 'display_name' => 'Editar clase real', 'group' => 'Clases Reales'],
            ['name' => 'real_classes.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar clase real', 'group' => 'Clases Reales'],

            ['name' => 'attendance_statuses.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar estados asistencia', 'group' => 'Estados Asistencia'],
            ['name' => 'attendance_statuses.view', 'guard_name' => 'web', 'display_name' => 'Ver estado asistencia', 'group' => 'Estados Asistencia'],
            ['name' => 'attendance_statuses.create', 'guard_name' => 'web', 'display_name' => 'Crear estado asistencia', 'group' => 'Estados Asistencia'],
            ['name' => 'attendance_statuses.update', 'guard_name' => 'web', 'display_name' => 'Editar estado asistencia', 'group' => 'Estados Asistencia'],
            ['name' => 'attendance_statuses.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar estado asistencia', 'group' => 'Estados Asistencia'],

            ['name' => 'attendances.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar asistencias', 'group' => 'Asistencias'],
            ['name' => 'attendances.view', 'guard_name' => 'web', 'display_name' => 'Ver asistencia', 'group' => 'Asistencias'],
            ['name' => 'attendances.monthlyRegister', 'guard_name' => 'web', 'display_name' => 'Ver registro mensual de asistencias', 'group' => 'Asistencias'],
            ['name' => 'attendances.byClassRealId', 'guard_name' => 'web', 'display_name' => 'Ver asistencias por clase', 'group' => 'Asistencias'],
            ['name' => 'attendances.create', 'guard_name' => 'web', 'display_name' => 'Tomar asistencia', 'group' => 'Asistencias'],
            ['name' => 'attendances.export', 'guard_name' => 'web', 'display_name' => 'Exportar asistencias', 'group' => 'Asistencias'],
            ['name' => 'attendances.update', 'guard_name' => 'web', 'display_name' => 'Editar asistencia', 'group' => 'Asistencias'],
            ['name'=> 'attendances.scan', 'guard_name' => 'web', 'display_name' => 'Escanear asistencia', 'group' => 'Asistencias'],
            ['name' => 'attendances.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar asistencia', 'group' => 'Asistencias'],

            // Notificaciones
            ['name' => 'notification_types.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar tipos notificación', 'group' => 'Tipos Notificación'],
            ['name' => 'notification_types.view', 'guard_name' => 'web', 'display_name' => 'Ver tipo notificación', 'group' => 'Tipos Notificación'],
            ['name' => 'notification_types.create', 'guard_name' => 'web', 'display_name' => 'Crear tipo notificación', 'group' => 'Tipos Notificación'],
            ['name' => 'notification_types.update', 'guard_name' => 'web', 'display_name' => 'Editar tipo notificación', 'group' => 'Tipos Notificación'],
            ['name' => 'notification_types.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar tipo notificación', 'group' => 'Tipos Notificación'],

            ['name' => 'notifications.all', 'guard_name' => 'web', 'display_name' => 'Ver todas las notificaciones', 'group' => 'Notificaciones'],
            ['name' => 'notifications.viewAny', 'guard_name' => 'web', 'display_name' => 'Ver mis notificaciones', 'group' => 'Notificaciones'],
            ['name' => 'notifications.view', 'guard_name' => 'web', 'display_name' => 'Ver detalle notificación', 'group' => 'Notificaciones'],
            ['name' => 'notifications.markAsRead', 'guard_name' => 'web', 'display_name' => 'Marcar como leída', 'group' => 'Notificaciones'],
            ['name' => 'notifications.markAllAsRead', 'guard_name' => 'web', 'display_name' => 'Marcar todas como leídas', 'group' => 'Notificaciones'],
            ['name' => 'notifications.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar notificación', 'group' => 'Notificaciones'],

            // Motivos Días Sin Clase
            ['name' => 'no_class_reasons.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar motivos días sin clase', 'group' => 'Motivos Días Sin Clase'],
            ['name' => 'no_class_reasons.view', 'guard_name' => 'web', 'display_name' => 'Ver motivo día sin clase', 'group' => 'Motivos Días Sin Clase'],
            ['name' => 'no_class_reasons.create', 'guard_name' => 'web', 'display_name' => 'Crear motivo día sin clase', 'group' => 'Motivos Días Sin Clase'],
            ['name' => 'no_class_reasons.update', 'guard_name' => 'web', 'display_name' => 'Editar motivo día sin clase', 'group' => 'Motivos Días Sin Clase'],
            ['name' => 'no_class_reasons.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar motivo día sin clase', 'group' => 'Motivos Días Sin Clase'],

            // Días sin clase
            ['name' => 'no_class_days.viewAny', 'guard_name' => 'web', 'display_name' => 'Listar días sin clase', 'group' => 'Días Sin Clase'],
            ['name' => 'no_class_days.view', 'guard_name' => 'web', 'display_name' => 'Ver día sin clase', 'group' => 'Días Sin Clase'],
            ['name' => 'no_class_days.check', 'guard_name' => 'web', 'display_name' => 'Consultar día sin clase', 'group' => 'Días Sin Clase'],
            ['name' => 'no_class_days.create', 'guard_name' => 'web', 'display_name' => 'Crear día sin clase', 'group' => 'Días Sin Clase'],
            ['name' => 'no_class_days.update', 'guard_name' => 'web', 'display_name' => 'Editar día sin clase', 'group' => 'Días Sin Clase'],
            ['name' => 'no_class_days.delete', 'guard_name' => 'web', 'display_name' => 'Eliminar día sin clase', 'group' => 'Días Sin Clase'],

            // Dashboards
            ['name' => 'attendance_dashboard.view', 'guard_name' => 'web', 'display_name' => 'Ver dashboard de asistencia', 'group' => 'Dashboards'],

        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
