<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\API\Apprentice\ApprenticeController;
use App\Http\Controllers\API\Area\AreaController;
use App\Http\Controllers\API\Attendance\AttendanceController;
use App\Http\Controllers\API\Attendance\MonthlyAttendanceRegisterController;
use App\Http\Controllers\API\AttendanceStatus\AttendanceStatusController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordResetController;
use App\Http\Controllers\API\Classroom\ClassroomController;
use App\Http\Controllers\API\ClassType\ClassTypeController;
use App\Http\Controllers\API\Dashboard\AttendanceDashboardController;
use App\Http\Controllers\API\Day\DayController;
use App\Http\Controllers\API\DocumentType\DocumentTypeController;
use App\Http\Controllers\API\EmailVerification\EmailVerificationController;
use App\Http\Controllers\API\Ficha\FichaController;
use App\Http\Controllers\API\FichaStatus\FichaStatusController;
use App\Http\Controllers\API\FichaTerm\FichaTermController;
use App\Http\Controllers\API\NoClassDay\NoClassDayController;
use App\Http\Controllers\API\NoClassReason\NoClassReasonController;
use App\Http\Controllers\API\Notification\NotificationController;
use App\Http\Controllers\API\NotificationType\NotificationTypeController;
use App\Http\Controllers\API\Permission\PermissionController;
use App\Http\Controllers\API\Phase\PhaseController;
use App\Http\Controllers\API\QualificationLevel\QualificationLevelController;
use App\Http\Controllers\API\RealClass\RealClassController;
use App\Http\Controllers\API\Role\RoleController;
use App\Http\Controllers\API\Schedule\ScheduleController;
use App\Http\Controllers\API\ScheduleSession\ScheduleSessionController;
use App\Http\Controllers\API\Shift\ShiftController;
use App\Http\Controllers\API\Term\TermController;
use App\Http\Controllers\API\TimeSlot\TimeSlotController;
use App\Http\Controllers\API\TrainingProgram\TrainingProgramController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\UserProfile\UserProfileController;
use App\Http\Controllers\API\UserStatus\UserStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/**
 * Archivo de rutas de la API REST.
 *
 * Organización en tres niveles de acceso:
 *
 * 1. PÚBLICO con throttle general (throttle:api):
 *    - Ruta de prueba/health check
 *    - Autenticación (con throttle:auth más restrictivo)
 *    - Verificación de email
 *
 * 2. PROTEGIDO (auth:sanctum + verified + acting.role):
 *    - Requiere token válido de Sanctum
 *    - Requiere email verificado
 *    - Requiere header X-Acting-Role-Id válido
 *    - Cada ruta tiene su propio middleware de permiso
 *
 * Convención de permisos:
 * - recurso.viewAny  → listar todos
 * - recurso.view     → ver uno
 * - recurso.viewOwn  → ver el propio
 * - recurso.create   → crear
 * - recurso.update   → actualizar
 * - recurso.delete   → eliminar
 *
 * Convención de parámetros de ruta:
 * - {recurso_id} → ID del recurso en formato snake_case
 */

/**
 * GRUPO RAÍZ: throttle:api (120 req/min).
 * Aplica a todas las rutas del archivo.
 */
Route::middleware('throttle:api')->group(function () {

    /**
     * Ruta de health check / prueba de conectividad.
     * Útil para monitorear que la API está activa.
     */
    Route::get('/prueba', function (Request $request) {
        return Response::json(['message' => 'La API está funcionando correctamente.'], 200);
    });

    Route::prefix('document_types')->group(function () {
        Route::get('/select', [DocumentTypeController::class, 'select']);
    });

    /**
     * GRUPO: Autenticación pública.
     *
     * throttle:auth = 10 req/min (más restrictivo para prevenir fuerza bruta).
     * Estas rutas no requieren autenticación previa.
     */
    Route::middleware('throttle:auth')->group(function () {
        // Registro de nuevo usuario
        Route::post('/register', [AuthController::class, 'register']);

        // Login: retorna access_token y refresh_token en cookies
        Route::post('/login', [AuthController::class, 'login']);

        /**
         * Refresh token: genera nuevo access_token.
         * Requiere:
         * - auth:sanctum: token válido (el refresh_token)
         * - verified: email verificado
         * - ability: token debe tener ability ISSUE_ACCESS_TOKEN
         */
        Route::post('/refresh_token', [AuthController::class, 'refreshToken'])
            ->middleware([
                'auth:sanctum',
                'verified',
                'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value
            ]);

        // Solicitar email de recuperación de contraseña
        Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);

        // Restablecer contraseña con token del email
        Route::post('/reset-password', [PasswordResetController::class, 'reset']);

        // Logout: invalida el token actual
        Route::post('/logout', [AuthController::class, 'logOut'])
            ->middleware(['auth:sanctum', 'verified', 'auth:sanctum']);
    });

    /**
     * GRUPO: Verificación de email.
     *
     * Rutas relacionadas con el flujo de verificación de email.
     * No requieren autenticación completa.
     */

    // Redirige al usuario si no ha verificado (muestra aviso)
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    // Procesa el enlace de verificación del email (URL firmada)
    // middleware 'signed': verifica que la URL no fue manipulada
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    // Reenvía el email de verificación
    // throttle:verification = 6 req/min para prevenir spam
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification')
        ->name('verification.send');

    /**
     * GRUPO: Rutas protegidas.
     *
     * Middleware aplicado a todas:
     * - auth:sanctum: requiere Bearer token válido
     * - verified: requiere email verificado
     * - acting.role: requiere header X-Acting-Role-Id válido
     *
     * Cada ruta agrega middleware 'permission:' para verificar
     * que el rol activo tiene el permiso específico requerido.
     */
    Route::middleware(['auth:sanctum', 'verified', 'acting.role'])->group(function () {

        /**
         * ROLES: Gestión de roles del sistema.
         *
         * Incluye sincronización de permisos (patch sync_permissions).
         */
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])
                ->middleware('permission:roles.viewAny');
            Route::get('/select', [RoleController::class, 'selectable'])
                ->middleware('permission:roles.viewAny');         // Lista simplificada para selects
            Route::get('/{role_id}', [RoleController::class, 'show'])
                ->middleware('permission:roles.view');
            Route::post('/', [RoleController::class, 'store'])
                ->middleware('permission:roles.create');
            Route::patch('/{role_id}/sync_permissions', [RoleController::class, 'syncPermissions'])
                ->middleware('permission:roles.update');          // Sincroniza permisos del rol
            Route::patch('/{role_id}', [RoleController::class, 'update'])
                ->middleware('permission:roles.update');
            Route::delete('/{role_id}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete');
        });

        /**
         * ESTADOS DE USUARIO: CRUD completo.
         * Incluye PUT (reemplazo total) y PATCH (actualización parcial).
         */
        Route::prefix('user_statuses')->group(function () {
            Route::get('/', [UserStatusController::class, 'index'])
                ->middleware('permission:user_statuses.viewAny');
            Route::get('/{status_id}', [UserStatusController::class, 'show'])
                ->middleware('permission:user_statuses.view');
            Route::post('/', [UserStatusController::class, 'store'])
                ->middleware('permission:user_statuses.create');
            Route::put('/{status_id}', [UserStatusController::class, 'update'])
                ->middleware('permission:user_statuses.update');          // Reemplazo total
            Route::patch('/{status_id}', [UserStatusController::class, 'partialUpdate'])
                ->middleware('permission:user_statuses.partialUpdate');   // Actualización parcial
            Route::delete('/{status_id}', [UserStatusController::class, 'destroy'])
                ->middleware('permission:user_statuses.delete');
        });

        /**
         * TIPOS DE DOCUMENTO: CRUD completo.
         */
        Route::prefix('document_types')->group(function () {
            Route::get('/', [DocumentTypeController::class, 'select'])
                ->middleware('permission:document_types.viewAny');
            Route::get('/{document_type_id}', [DocumentTypeController::class, 'show'])
                ->middleware('permission:document_types.view');
            Route::post('/', [DocumentTypeController::class, 'store'])
                ->middleware('permission:document_types.create');
            Route::patch('/{document_type_id}', [DocumentTypeController::class, 'update'])
                ->middleware('permission:document_types.update');
            Route::delete('/{document_type_id}', [DocumentTypeController::class, 'destroy'])
                ->middleware('permission:document_types.delete');
        });

        /**
         * USUARIOS: CRUD + rutas especiales.
         *
         * Rutas especiales:
         * - /me: perfil del usuario autenticado
         * - /role/{role_code}: usuarios filtrados por rol
         * - /me/password: cambio de contraseña propia
         */
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('permission:users.viewAny');
            Route::get('/role/{role_code}', [UserController::class, 'indexByRole'])
                ->middleware('permission:users.viewAny');           // Filtrar por rol
            Route::get('/me', [UserController::class, 'showOwn'])
                ->middleware('permission:users.viewOwn');           // Propio perfil
            Route::get('/{user_id}', [UserController::class, 'show'])
                ->middleware('permission:users.view');
            Route::post('/', [UserController::class, 'store'])
                ->middleware('permission:users.create');
            Route::patch('/{user_id}', [UserController::class, 'update'])
                ->middleware('permission:users.update');
            Route::patch('/me/password', [UserController::class, 'updateOwnPassword'])
                ->middleware('permission:users.updateOwnPassword'); // Cambiar propia contraseña
            Route::delete('/{id}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete');
        });

        /**
         * PERFILES DE USUARIO: Gestión de datos personales.
         *
         * Separa la gestión de perfiles de la gestión de usuarios.
         * Incluye rutas /me para acceso al propio perfil.
         */
        Route::prefix('profiles/users')->group(function () {
            Route::get('/', [UserProfileController::class, 'index'])
                ->middleware('permission:profiles_users.viewAny');
            Route::get('/me', [UserProfileController::class, 'showOwn'])
                ->middleware('permission:profiles_users.viewOwn');
            Route::get('/profile/{profile_id}', [UserProfileController::class, 'show'])
                ->middleware('permission:profiles_users.view');
            Route::get('/user/{user_id}', [UserProfileController::class, 'showByUser'])
                ->middleware('permission:profiles_users.viewByUser');
            Route::patch('/me', [UserProfileController::class, 'updateOwn'])
                ->middleware('permission:profiles_users.updateOwn');
            Route::patch('/user/{user_id}', [UserProfileController::class, 'update'])
                ->middleware('permission:profiles_users.update');
        });

        /**
         * ÁREAS: CRUD + ruta select simplificada.
         */
        Route::prefix('areas')->group(function () {
            Route::get('/', [AreaController::class, 'index'])
                ->middleware('permission:areas.viewAny');
            Route::get('/select', [AreaController::class, 'select'])
                ->middleware('permission:areas.viewAny');  // Lista simplificada para selects
            Route::get('/{area_id}', [AreaController::class, 'show'])
                ->middleware('permission:areas.view');
            Route::post('/', [AreaController::class, 'store'])
                ->middleware('permission:areas.create');
            Route::patch('/{area_id}', [AreaController::class, 'update'])
                ->middleware('permission:areas.update');
            Route::delete('/{area_id}', [AreaController::class, 'destroy'])
                ->middleware('permission:areas.delete');
        });

        /**
         * NIVELES DE CUALIFICACIÓN: CRUD completo.
         * (Técnico, Tecnólogo, Especialización, etc.)
         */
        Route::prefix('qualification_levels')->group(function () {
            Route::get('/', [QualificationLevelController::class, 'index'])
                ->middleware('permission:qualification_levels.viewAny');
            Route::get('/{qualification_level_id}', [QualificationLevelController::class, 'show'])
                ->middleware('permission:qualification_levels.view');
            Route::post('/', [QualificationLevelController::class, 'store'])
                ->middleware('permission:qualification_levels.create');
            Route::patch('/{qualification_level_id}', [QualificationLevelController::class, 'update'])
                ->middleware('permission:qualification_levels.update');
            Route::delete('/{qualification_level_id}', [QualificationLevelController::class, 'destroy'])
                ->middleware('permission:qualification_levels.delete');
        });

        /**
         * PROGRAMAS DE FORMACIÓN: CRUD + select simplificado.
         */
        Route::prefix('training_programs')->group(function () {
            Route::get('/', [TrainingProgramController::class, 'index'])
                ->middleware('permission:training_programs.viewAny');
            Route::get('/select', [TrainingProgramController::class, 'select'])
                ->middleware('permission:training_programs.viewAny');
            Route::get('/{training_program_id}', [TrainingProgramController::class, 'show'])
                ->middleware('permission:training_programs.view');
            Route::post('/', [TrainingProgramController::class, 'store'])
                ->middleware('permission:training_programs.create');
            Route::patch('/{training_program_id}', [TrainingProgramController::class, 'update'])
                ->middleware('permission:training_programs.update');
            Route::delete('/{training_program_id}', [TrainingProgramController::class, 'destroy'])
                ->middleware('permission:training_programs.delete');
        });

        /**
         * ESTADOS DE FICHA: CRUD completo.
         */
        Route::prefix('ficha_statuses')->group(function () {
            Route::get('/', [FichaStatusController::class, 'index'])
                ->middleware('permission:ficha_statuses.viewAny');
            Route::get('/{status_id}', [FichaStatusController::class, 'show'])
                ->middleware('permission:ficha_statuses.view');
            Route::post('/', [FichaStatusController::class, 'store'])
                ->middleware('permission:ficha_statuses.create');
            Route::patch('/{status_id}', [FichaStatusController::class, 'update'])
                ->middleware('permission:ficha_statuses.update');
            Route::delete('/{status_id}', [FichaStatusController::class, 'destroy'])
                ->middleware('permission:ficha_statuses.delete');
        });

        /**
         * FICHAS: CRUD + rutas especiales.
         *
         * Rutas especiales:
         * - /select: lista simplificada para selects del frontend
         * - /available_for_real_class: fichas que pueden tener clases reales
         * - /training_program/{id}: fichas por programa de formación
         */
        Route::prefix('fichas')->group(function () {
            Route::get('/', [FichaController::class, 'index'])
                ->middleware('permission:fichas.viewAny');
            Route::get('/select', [FichaController::class, 'select'])
                ->middleware('permission:fichas.viewAny');
            Route::get('/available_for_real_class', [FichaController::class, 'availableForRealClass'])
                ->middleware('permission:fichas.availableForRealClass'); // Fichas con trimestre activo
            Route::get('/training_program/{training_program_id}', [FichaController::class, 'showByTrainingProgram'])
                ->middleware('permission:fichas.viewAny');
            Route::get('/{ficha_id}', [FichaController::class, 'show'])
                ->middleware('permission:fichas.view');
            Route::post('/', [FichaController::class, 'store'])
                ->middleware('permission:fichas.create');
            Route::patch('/{ficha_id}', [FichaController::class, 'update'])
                ->middleware('permission:fichas.update');
            Route::delete('/{ficha_id}', [FichaController::class, 'destroy'])
                ->middleware('permission:fichas.delete');
        });

        /**
         * APRENDICES: CRUD + importación masiva.
         *
         * Rutas especiales:
         * - /template/download: descarga plantilla Excel para importación
         * - /import: importa aprendices desde Excel (ApprenticesImport)
         */
        Route::prefix('apprentices')->group(function () {
            Route::get('/', [ApprenticeController::class, 'index'])
                ->middleware('permission:apprentices.viewAny');
            Route::get('/template/download', [ApprenticeController::class, 'downloadTemplate'])
                ->middleware('permission:apprentices.import');     // Descarga plantilla Excel
            Route::get('/{apprentice_id}', [ApprenticeController::class, 'show'])
                ->middleware('permission:apprentices.view');
            Route::post('/', [ApprenticeController::class, 'store'])
                ->middleware('permission:apprentices.create');
            Route::patch('/{apprentice_id}', [ApprenticeController::class, 'update'])
                ->middleware('permission:apprentices.update');
            Route::delete('/{apprentice_id}', [ApprenticeController::class, 'destroy'])
                ->middleware('permission:apprentices.delete');
            Route::post('/import', [ApprenticeController::class, 'import'])
                ->middleware('permission:apprentices.import');     // Importación masiva desde Excel
        });

        /**
         * TRIMESTRES (TERMS): CRUD completo.
         * Períodos académicos globales del sistema.
         */
        Route::prefix('terms')->group(function () {
            Route::get('/', [TermController::class, 'index'])
                ->middleware('permission:terms.viewAny');
            Route::get('/{term_id}', [TermController::class, 'show'])
                ->middleware('permission:terms.view');
            Route::post('/', [TermController::class, 'store'])
                ->middleware('permission:terms.create');
            Route::patch('/{term_id}', [TermController::class, 'update'])
                ->middleware('permission:terms.update');
            Route::delete('/{term_id}', [TermController::class, 'destroy'])
                ->middleware('permission:terms.delete');
        });

        /**
         * FASES: CRUD completo.
         * Fases del proceso formativo (Lectiva, Productiva, etc.)
         */
        Route::prefix('phases')->group(function () {
            Route::get('/', [PhaseController::class, 'index'])
                ->middleware('permission:phases.viewAny');
            Route::get('/{phase_id}', [PhaseController::class, 'show'])
                ->middleware('permission:phases.view');
            Route::post('/', [PhaseController::class, 'store'])
                ->middleware('permission:phases.create');
            Route::patch('/{phase_id}', [PhaseController::class, 'update'])
                ->middleware('permission:phases.update');
            Route::delete('/{phase_id}', [PhaseController::class, 'destroy'])
                ->middleware('permission:phases.delete');
        });

        /**
         * TRIMESTRES DE FICHA (FICHA_TERMS): CRUD + acción especial.
         *
         * Asocia un trimestre global a una ficha específica.
         *
         * Ruta especial:
         * - /{id}/set_current: marca este trimestre como el activo de la ficha
         */
        Route::prefix('ficha_terms')->group(function () {
            Route::get('/', [FichaTermController::class, 'index'])
                ->middleware('permission:ficha_terms.viewAny');
            Route::get('/{ficha_term_id}', [FichaTermController::class, 'show'])
                ->middleware('permission:ficha_terms.view');
            Route::post('/', [FichaTermController::class, 'store'])
                ->middleware('permission:ficha_terms.create');
            Route::patch('/{ficha_term_id}', [FichaTermController::class, 'update'])
                ->middleware('permission:ficha_terms.update');
            Route::patch('/{ficha_term_id}/set_current', [FichaTermController::class, 'setCurrent'])
                ->middleware('permission:ficha_terms.setCurrent'); // Establece trimestre activo
            Route::delete('/{ficha_term_id}', [FichaTermController::class, 'destroy'])
                ->middleware('permission:ficha_terms.delete');
        });

        /**
         * HORARIOS (SCHEDULES): CRUD + consulta por trimestre.
         *
         * Ruta especial:
         * - /ficha_term/{id}: horario del trimestre de una ficha
         */
        Route::prefix('schedules')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])
                ->middleware('permission:schedules.viewAny');
            Route::get('/{schedule_id}', [ScheduleController::class, 'show'])
                ->middleware('permission:schedules.view');
            Route::get('/ficha_term/{ficha_term_id}', [ScheduleController::class, 'showByFichaTerm'])
                ->middleware('permission:schedules.view');          // Horario por trimestre
            Route::post('/', [ScheduleController::class, 'store'])
                ->middleware('permission:schedules.create');
            Route::patch('/{schedule_id}', [ScheduleController::class, 'update'])
                ->middleware('permission:schedules.update');
            Route::delete('/{schedule_id}', [ScheduleController::class, 'destroy'])
                ->middleware('permission:schedules.delete');
        });

        /**
         * DÍAS DE LA SEMANA (DAYS): CRUD completo.
         * (Lunes, Martes, ..., Domingo)
         */
        Route::prefix('days')->group(function () {
            Route::get('/', [DayController::class, 'index'])
                ->middleware('permission:days.viewAny');
            Route::get('/{day_id}', [DayController::class, 'show'])
                ->middleware('permission:days.view');
            Route::post('/', [DayController::class, 'store'])
                ->middleware('permission:days.create');
            Route::patch('/{day_id}', [DayController::class, 'update'])
                ->middleware('permission:days.update');
            Route::delete('/{day_id}', [DayController::class, 'destroy'])
                ->middleware('permission:days.delete');
        });

        /**
         * JORNADAS (SHIFTS): CRUD completo.
         * (Mañana, Tarde, Noche)
         */
        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftController::class, 'index'])
                ->middleware('permission:shifts.viewAny');
            Route::get('/{shift_id}', [ShiftController::class, 'show'])
                ->middleware('permission:shifts.view');
            Route::post('/', [ShiftController::class, 'store'])
                ->middleware('permission:shifts.create');
            Route::patch('/{shift_id}', [ShiftController::class, 'update'])
                ->middleware('permission:shifts.update');
            Route::delete('/{shift_id}', [ShiftController::class, 'destroy'])
                ->middleware('permission:shifts.delete');
        });

        /**
         * FRANJAS HORARIAS (TIME_SLOTS): CRUD completo.
         * Bloques de tiempo dentro de una jornada.
         */
        Route::prefix('time_slots')->group(function () {
            Route::get('/', [TimeSlotController::class, 'index'])
                ->middleware('permission:time_slots.viewAny');
            Route::get('/{time_slot_id}', [TimeSlotController::class, 'show'])
                ->middleware('permission:time_slots.view');
            Route::post('/', [TimeSlotController::class, 'store'])
                ->middleware('permission:time_slots.create');
            Route::patch('/{time_slot_id}', [TimeSlotController::class, 'update'])
                ->middleware('permission:time_slots.update');
            Route::delete('/{time_slot_id}', [TimeSlotController::class, 'destroy'])
                ->middleware('permission:time_slots.delete');
        });

        /**
         * AMBIENTES/SALONES (CLASSROOMS): CRUD completo.
         */
        Route::prefix('classrooms')->group(function () {
            Route::get('/', [ClassroomController::class, 'index'])
                ->middleware('permission:classrooms.viewAny');
            Route::get('/select', [ClassroomController::class, 'select'])
                ->middleware('permission:classrooms.viewAny');
            Route::get('/{classroom:id}', [ClassroomController::class, 'show'])
                ->middleware('permission:classrooms.view');
            Route::post('/', [ClassroomController::class, 'store'])
                ->middleware('permission:classrooms.create');
            Route::patch('/{classroom:id}', [ClassroomController::class, 'update'])
                ->middleware('permission:classrooms.update');
            Route::delete('/{classroom:id}', [ClassroomController::class, 'destroy'])
                ->middleware('permission:classrooms.delete');
        });

        /**
         * SESIONES DE HORARIO (SCHEDULE_SESSIONS): CRUD + consulta por ficha.
         *
         * Una sesión de horario define día/hora/instructor/ambiente para una clase.
         *
         * Ruta especial:
         * - /ficha/{ficha_id}: sesiones del horario activo de una ficha
         */
        Route::prefix('schedule_sessions')->group(function () {
            Route::get('/', [ScheduleSessionController::class, 'index'])
                ->middleware('permission:schedule_sessions.viewAny');
            Route::get('/{schedule_session_id}', [ScheduleSessionController::class, 'show'])
                ->middleware('permission:schedule_sessions.view');
            Route::get('/ficha/{ficha_id}', [ScheduleSessionController::class, 'showByFicha'])
                ->middleware('permission:schedule_sessions.byFichaId'); // Sesiones de una ficha
            Route::post('/', [ScheduleSessionController::class, 'store'])
                ->middleware('permission:schedule_sessions.create');
            Route::patch('/{schedule_session_id}', [ScheduleSessionController::class, 'update'])
                ->middleware('permission:schedule_sessions.update');
            Route::delete('/{schedule_session_id}', [ScheduleSessionController::class, 'destroy'])
                ->middleware('permission:schedule_sessions.delete');
        });

        /**
         * TIPOS DE CLASE (CLASS_TYPES): CRUD completo.
         * (Normal, Recuperación, Reposición, etc.)
         */
        Route::prefix('class_types')->group(function () {
            Route::get('/', [ClassTypeController::class, 'index'])
                ->middleware('permission:class_types.viewAny');
            Route::get('/{class_type_id}', [ClassTypeController::class, 'show'])
                ->middleware('permission:class_types.view');
            Route::post('/', [ClassTypeController::class, 'store'])
                ->middleware('permission:class_types.create');
            Route::patch('/{class_type_id}', [ClassTypeController::class, 'update'])
                ->middleware('permission:class_types.update');
            Route::delete('/{class_type_id}', [ClassTypeController::class, 'destroy'])
                ->middleware('permission:class_types.delete');
        });

        /**
         * CLASES REALES (REAL_CLASSES): CRUD + rutas de scope por rol.
         *
         * Rutas especiales por rol:
         * - /mine: clases donde el usuario es instructor (rol INSTRUCTOR)
         * - /managed: clases de fichas gestionadas (rol GESTOR_FICHAS)
         *
         * La creación usa RealClassPolicy para validar permisos granulares.
         */
        Route::prefix('real_classes')->group(function () {
            Route::get('/', [RealClassController::class, 'index'])
                ->middleware('permission:real_classes.viewAny');
            Route::get('/mine', [RealClassController::class, 'mine'])
                ->middleware('permission:real_classes.viewOwn');    // Instructor: sus clases
            Route::get('/managed', [RealClassController::class, 'managed'])
                ->middleware('permission:real_classes.viewManaged'); // Gestor: clases de sus fichas
            Route::get('/{real_class_id}', [RealClassController::class, 'show'])
                ->middleware('permission:real_classes.view');
            Route::post('/', [RealClassController::class, 'store'])
                ->middleware('permission:real_classes.create');     // + RealClassPolicy
            Route::patch('/{real_class_id}', [RealClassController::class, 'update'])
                ->middleware('permission:real_classes.update');
            Route::delete('/{real_class_id}', [RealClassController::class, 'destroy'])
                ->middleware('permission:real_classes.delete');
        });

        /**
         * ESTADOS DE ASISTENCIA (ATTENDANCE_STATUSES): CRUD completo.
         * (Presente, Ausente, Tarde, Justificado, Salida anticipada, Sin registrar)
         */
        Route::prefix('attendance_statuses')->group(function () {
            Route::get('/', [AttendanceStatusController::class, 'index'])
                ->middleware('permission:attendance_statuses.viewAny');
            Route::get('/{attendance_status_id}', [AttendanceStatusController::class, 'show'])
                ->middleware('permission:attendance_statuses.view');
            Route::post('/', [AttendanceStatusController::class, 'store'])
                ->middleware('permission:attendance_statuses.create');
            Route::patch('/{attendance_status_id}', [AttendanceStatusController::class, 'update'])
                ->middleware('permission:attendance_statuses.update');
            Route::delete('/{attendance_status_id}', [AttendanceStatusController::class, 'destroy'])
                ->middleware('permission:attendance_statuses.delete');
        });

        /**
         * ASISTENCIAS (ATTENDANCES): CRUD + rutas especiales.
         *
         * Rutas especiales:
         * - /monthly_register: registro mensual de asistencias (vista compleja)
         * - /file/export: exporta registro mensual a Excel
         * - /scan: registro de asistencia por QR (sin middleware de permiso)
         * - /class/{real_class_id}: asistencias de una clase específica
         */
        Route::prefix('attendances')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])
                ->middleware('permission:attendances.viewAny');
            Route::get('/monthly_register', [MonthlyAttendanceRegisterController::class, 'show'])
                ->middleware('permission:attendances.monthlyRegister'); // Vista del registro mensual
            Route::get('/{attendance_id}', [AttendanceController::class, 'show'])
                ->middleware('permission:attendances.view');
            Route::get('/file/export', [AttendanceController::class, 'exportMonthlyRegister'])
                ->middleware('permission:attendances.export');          // Exportación Excel
            Route::post('/', [AttendanceController::class, 'store'])
                ->middleware('permission:attendances.create');
            Route::patch('/{attendance_id}', [AttendanceController::class, 'update'])
                ->middleware('permission:attendances.update');
            Route::post('/scan', [AttendanceController::class, 'scan']);  // QR scan (sin permiso extra)
            Route::delete('/{attendance_id}', [AttendanceController::class, 'destroy'])
                ->middleware('permission:attendances.delete');
            Route::get('/class/{real_class_id}', [AttendanceController::class, 'byClassRealId'])
                ->middleware('permission:attendances.byClassRealId');   // Por clase real
        });

        /**
         * TIPOS DE NOTIFICACIÓN: CRUD completo.
         * (resource_created, resource_updated, apprentice_became_adult, etc.)
         */
        Route::prefix('notification_types')->group(function () {
            Route::get('/', [NotificationTypeController::class, 'index'])
                ->middleware('permission:notification_types.viewAny');
            Route::get('/{notification_type_id}', [NotificationTypeController::class, 'show'])
                ->middleware('permission:notification_types.view');
            Route::post('/', [NotificationTypeController::class, 'store'])
                ->middleware('permission:notification_types.create');
            Route::patch('/{notification_type_id}', [NotificationTypeController::class, 'update'])
                ->middleware('permission:notification_types.update');
            Route::delete('/{notification_type_id}', [NotificationTypeController::class, 'destroy'])
                ->middleware('permission:notification_types.delete');
        });

        /**
         * NOTIFICACIONES: Gestión de notificaciones del usuario.
         *
         * Rutas especiales:
         * - /all: todas las notificaciones del sistema (admin)
         * - /me: notificaciones del usuario autenticado (paginadas)
         * - /latest: últimas N notificaciones (para el popover campana)
         * - /unread_count: contador de no leídas
         * - /read_all: marca todas como leídas
         * - /{id}/read: marca una como leída
         */
        Route::prefix('notifications')->group(function () {
            Route::get('/all', [NotificationController::class, 'all'])
                ->middleware('permission:notifications.all');           // Admin: todas
            Route::get('/me', [NotificationController::class, 'index'])
                ->middleware('permission:notifications.viewAny');       // Paginadas del usuario
            Route::get('/latest', [NotificationController::class, 'latest'])
                ->middleware('permission:notifications.viewAny');       // Últimas N (campana)
            Route::get('/unread_count', [NotificationController::class, 'unreadCount'])
                ->middleware('permission:notifications.viewAny');       // Contador badge
            Route::patch('/read_all', [NotificationController::class, 'markAllAsRead'])
                ->middleware('permission:notifications.markAllAsRead'); // Marcar todas leídas
            Route::get('/{notification_id}', [NotificationController::class, 'show'])
                ->middleware('permission:notifications.view');
            Route::patch('/{notification_id}/read', [NotificationController::class, 'markAsRead'])
                ->middleware('permission:notifications.markAsRead');    // Marcar una leída
            Route::delete('/{notification_id}', [NotificationController::class, 'destroy'])
                ->middleware('permission:notifications.delete');
        });

        /**
         * RAZONES DE DÍA SIN CLASE: CRUD completo.
         *
         * Nota: estas rutas no tienen middleware de permiso individual.
         * Heredan solo los middlewares del grupo padre (auth, verified, acting.role).
         */
        Route::prefix('no_class_reasons')->group(function () {
            Route::get('/', [NoClassReasonController::class, 'index'])
                ->middleware('permission:no_class_reasons.viewAny');
            Route::get('/{no_class_reason_id}', [NoClassReasonController::class, 'show'])
                ->middleware('permission:no_class_reasons.view');
            Route::post('/', [NoClassReasonController::class, 'store'])
                ->middleware('permission:no_class_reasons.create');
            Route::put('/{no_class_reason_id}', [NoClassReasonController::class, 'update'])
                ->middleware('permission:no_class_reasons.update');
            Route::delete('/{no_class_reason_id}', [NoClassReasonController::class, 'destroy'])
                ->middleware('permission:no_class_reasons.delete');
        });

        /**
         * DÍAS SIN CLASE: CRUD + verificación.
         *
         * Ruta especial:
         * - /check: verifica si una fecha es día sin clase para una ficha
         *   Query params: ?ficha_id=&date=
         */
        Route::prefix('no_class_days')->group(function () {
            Route::get('/', [NoClassDayController::class, 'index'])
                ->middleware('permission:no_class_days.viewAny');
            Route::get('/check', [NoClassDayController::class, 'check'])
                ->middleware('permission:no_class_days.check');  // ?ficha_id=&date=
            Route::get('/{no_class_day_id}', [NoClassDayController::class, 'show'])
                ->middleware('permission:no_class_days.view');
            Route::post('/', [NoClassDayController::class, 'store'])
                ->middleware('permission:no_class_days.create');
            Route::patch('/{no_class_day_id}', [NoClassDayController::class, 'update'])
                ->middleware('permission:no_class_days.update');
            Route::delete('/{no_class_day_id}', [NoClassDayController::class, 'destroy'])
                ->middleware('permission:no_class_days.delete');
        });

        /**
         * DASHBOARD DE ASISTENCIAS: Vista analítica.
         *
         * Controlador invocable (single action controller).
         * Retorna métricas y gráficos de asistencias.
         */
        Route::prefix('dashboard')->group(function () {
            Route::get('/attendance', AttendanceDashboardController::class)
                ->middleware('permission:attendance_dashboard.view');
        });

        /**
         * PERMISOS: Listado para asignación a roles.
         *
         * Solo expone /select (lista simplificada).
         * Requiere permiso de actualizar roles (para asignación).
         */
        Route::prefix('permissions')->group(function () {
            Route::get('/select', [PermissionController::class, 'select'])
                ->middleware('permission:roles.update'); // Mismo permiso que editar roles
        });
    });
});
