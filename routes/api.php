<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\API\Apprentice\ApprenticeController;
use App\Http\Controllers\API\Area\AreaController;
use App\Http\Controllers\API\Attendance\AttendanceController;
use App\Http\Controllers\API\AttendanceStatus\AttendanceStatusController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Classroom\ClassroomController;
use App\Http\Controllers\API\ClassType\ClassTypeController;
use App\Http\Controllers\API\Day\DayController;
use App\Http\Controllers\API\DocumentType\DocumentTypeController;
use App\Http\Controllers\API\EmailVerification\EmailVerificationController;
use App\Http\Controllers\API\Ficha\FichaController;
use App\Http\Controllers\API\FichaStatus\FichaStatusController;
use App\Http\Controllers\API\FichaTerm\FichaTermController;
use App\Http\Controllers\API\NotificationType\NotificationTypeController;
use App\Http\Controllers\API\Phase\PhaseController;
use App\Http\Controllers\API\QualificationLevel\QualificationLevelController;
use App\Http\Controllers\API\RealClass\RealClassController;
use App\Http\Controllers\API\Role\RoleController;
use App\Http\Controllers\API\Schedule\ScheduleController;
use App\Http\Controllers\API\ScheduleSession\ScheduleSessionController;
use App\Http\Controllers\API\Shift\ShiftController;
use App\Http\Controllers\API\Term\TermController;
use App\Http\Controllers\API\TrainingProgram\TrainingProgramController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\UserProfile\UserProfileController;
use App\Http\Controllers\API\UserStatus\UserStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {

  Route::get('/prueba', function (Request $request) {
    return response()->json(['message' => 'La API está funcionando correctamente.'], 200);
  });

  // Rutas de autenticación
  Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh_token', [AuthController::class, 'refreshToken'])
      ->middleware(['auth:sanctum', 'verified', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value]);

    Route::post('/logout', [AuthController::class, 'logOut'])
      ->middleware(['auth:sanctum', 'verified']);
  });

  // Rutas de verificación de correo electrónico
  Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->name('verification.notice');

  Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

  Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:verification')
    ->name('verification.send');


  // Rutas casuales del sistema
  Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Rutas para Roles
    Route::prefix('roles')->group(function () {
      Route::get('/', [RoleController::class, 'index']);
      Route::get('/{role_id}', [RoleController::class, 'show']);
      Route::post('/', [RoleController::class, 'store']);
      Route::put('/{role_id}', [RoleController::class, 'update']);
      Route::delete('/{role_id}', [RoleController::class, 'destroy']);
    });


    // Rutas para User Status
    Route::prefix('user_statuses')->group(function () {
      Route::get('/', [UserStatusController::class, 'index']);
      Route::get('/{status_id}', [UserStatusController::class, 'show']);
      Route::post('/', [UserStatusController::class, 'store']);
      Route::put('/{status_id}', [UserStatusController::class, 'update']);
      Route::patch('/{status_id}', [UserStatusController::class, 'partialUpdate']);
      Route::delete('/{status_id}', [UserStatusController::class, 'destroy']);
    });


    //Rutas para tipos de documento
    Route::prefix('document_types')->group(function () {
      Route::get('/', [DocumentTypeController::class, 'index']);
      Route::get('/{document_type_id}', [DocumentTypeController::class, 'show']);
      Route::post('/', [DocumentTypeController::class, 'store']);
      Route::put('/{document_type_id}', [DocumentTypeController::class, 'update']);
      Route::patch('/{document_type_id}', [DocumentTypeController::class, 'partialUpdate']);
      Route::delete('/{document_type_id}', [DocumentTypeController::class, 'destroy']);
    });


    //Rutas para usuarios
    Route::prefix('users')->group(function () {
      Route::get('/', [UserController::class, 'index']);
      Route::get('/me', [UserController::class, 'showOwn']);
      Route::get('/{user_id}', [UserController::class, 'show']);
      Route::post('/', [UserController::class, 'store']);
      Route::patch('/{user_id}', [UserController::class, 'update']);
      Route::patch('/{user_id}/roles', [UserController::class, 'updateRoles']);
      Route::patch('/me/password', [UserController::class, 'updateOwnPassword']);
      Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    //Rutas para perfiles de usuario
    Route::prefix('profiles/users')->group(function () {
      Route::get('/', [UserProfileController::class, 'index']);
      Route::get('/me', [UserProfileController::class, 'showOwn']);
      Route::get('/profile/{profile_id}', [UserProfileController::class, 'show']);
      Route::get('/user/{user_id}', [UserProfileController::class, 'showByUser']);
      Route::patch('/me', [UserProfileController::class, 'updateOwn']);
      Route::patch('/user/{user_id}', [UserProfileController::class, 'update']);
    });


    //Rutas para areas
    Route::prefix('areas')->group(function () {
      Route::get('/', [AreaController::class, 'index']);
      Route::get('/{area_id}', [AreaController::class, 'show']);
      Route::post('/', [AreaController::class, 'store']);
      Route::patch('/{area_id}', [AreaController::class, 'update']);
      Route::delete('/{area_id}', [AreaController::class, 'destroy']);
    });


    //Rutas para niveles de formación
    Route::prefix('qualification_levels')->group(function () {
      Route::get('/', [QualificationLevelController::class, 'index']);
      Route::get('/{qualification_level_id}', [QualificationLevelController::class, 'show']);
      Route::post('/', [QualificationLevelController::class, 'store']);
      Route::patch('/{qualification_level_id}', [QualificationLevelController::class, 'update']);
      Route::delete('/{qualification_level_id}', [QualificationLevelController::class, 'destroy']);
    });


    //Rutas para programas de formación
    Route::prefix('training_programs')->group(function () {
      Route::get('/', [TrainingProgramController::class, 'index']);
      Route::get('/{training_program_id}', [TrainingProgramController::class, 'show']);
      Route::post('/', [TrainingProgramController::class, 'store']);
      Route::patch('/{training_program_id}', [TrainingProgramController::class, 'update']);
      Route::delete('/{training_program_id}', [TrainingProgramController::class, 'destroy']);
    });


    //Rutas para estados de ficha
    Route::prefix('ficha_statuses')->group(function () {
      Route::get('/', [FichaStatusController::class, 'index']);
      Route::get('/{status_id}', [FichaStatusController::class, 'show']);
      Route::post('/', [FichaStatusController::class, 'store']);
      Route::patch('/{status_id}', [FichaStatusController::class, 'update']);
      Route::delete('/{status_id}', [FichaStatusController::class, 'destroy']);
    });


    // Rutas para fichas
    Route::prefix('fichas')->group(function () {
      Route::get('/', [FichaController::class, 'index']);
      Route::get('/{ficha_id}', [FichaController::class, 'show']);
      Route::post('/', [FichaController::class, 'store']);
      Route::patch('/{ficha_id}', [FichaController::class, 'update']);
      Route::delete('/{ficha_id}', [FichaController::class, 'destroy']);
    });


    //Rutas para aprendices
    Route::prefix('apprentices')->group(function () {
      Route::get('/', [ApprenticeController::class, 'index']);
      Route::get('/{apprentice_id}', [ApprenticeController::class, 'show']);
      Route::post('/', [ApprenticeController::class, 'store']);
      Route::patch('/{apprentice_id}', [ApprenticeController::class, 'update']);
      Route::delete('/{apprentice_id}', [ApprenticeController::class, 'destroy']);
      Route::post('/import', [ApprenticeController::class, 'import']);
    });


    // Rutas para trimestres
    Route::prefix('terms')->group(function () {
      Route::get('/', [TermController::class, 'index']);
      Route::get('/{term_id}', [TermController::class, 'show']);
      Route::post('/', [TermController::class, 'store']);
      Route::patch('/{term_id}', [TermController::class, 'update']);
      Route::delete('/{term_id}', [TermController::class, 'destroy']);
    });


    //Rutas para fases de formación
    Route::prefix('phases')->group(function () {
      Route::get('/', [PhaseController::class, 'index']);
      Route::get('/{phase_id}', [PhaseController::class, 'show']);
      Route::post('/', [PhaseController::class, 'store']);
      Route::patch('/{phase_id}', [PhaseController::class, 'update']);
      Route::delete('/{phase_id}', [PhaseController::class, 'destroy']);
    });


    // Rutas para trimestres de las fichas
    Route::prefix('ficha_terms')->group(function () {
      Route::get('/', [FichaTermController::class, 'index']);
      Route::get('/{ficha_term_id}', [FichaTermController::class, 'show']);
      Route::post('/', [FichaTermController::class, 'store']);
      Route::patch('/{ficha_term_id}', [FichaTermController::class, 'update']);
      Route::patch('/{ficha_term_id}/set_current', [FichaTermController::class, 'setCurrent']);
      Route::delete('/{ficha_term_id}', [FichaTermController::class, 'destroy']);
    });


    // Rutas para bloques de horarios
    Route::prefix('schedules')->group(function () {
      Route::get('/', [ScheduleController::class, 'index']);
      Route::get('/{schedule_id}', [ScheduleController::class, 'show']);
      Route::post('/', [ScheduleController::class, 'store']);
      Route::patch('/{schedule_id}', [ScheduleController::class, 'update']);
      Route::delete('/{schedule_id}', [ScheduleController::class, 'destroy']);
    });


    //Rutas para los días
    Route::prefix('days')->group(function () {
      Route::get('/', [DayController::class, 'index']);
      Route::get('/{day_id}', [DayController::class, 'show']);
      Route::post('/', [DayController::class, 'store']);
      Route::patch('/{day_id}', [DayController::class, 'update']);
      Route::delete('/{day_id}', [DayController::class, 'destroy']);
    });


    //Rutas para las jornadas
    Route::prefix('shifts')->group(function () {
      Route::get('/', [ShiftController::class, 'index']);
      Route::get('/{shift_id}', [ShiftController::class, 'show']);
      Route::post('/', [ShiftController::class, 'store']);
      Route::patch('/{shift_id}', [ShiftController::class, 'update']);
      Route::delete('/{shift_id}', [ShiftController::class, 'destroy']);
    });


    //Rutas para ambientes de formacion
    Route::prefix('classrooms')->group(function () {
      Route::get('/', [ClassroomController::class, 'index']);
      Route::get('/{classroom_id}', [ClassroomController::class, 'show']);
      Route::post('/', [ClassroomController::class, 'store']);
      Route::patch('/{classroom_id}', [ClassroomController::class, 'update']);
      Route::delete('/{classroom_id}', [ClassroomController::class, 'destroy']);
    });


    //Rutas de sesiones de horario
    Route::prefix('schedule_sessions')->group(function () {
      Route::get('/', [ScheduleSessionController::class, 'index']);
      Route::get('/{schedule_session_id}', [ScheduleSessionController::class, 'show']);
      Route::post('/', [ScheduleSessionController::class, 'store']);
      Route::patch('/{schedule_session_id}', [ScheduleSessionController::class, 'update']);
      Route::delete('/{schedule_session_id}', [ScheduleSessionController::class, 'destroy']);
    });


    //Rutas de tipos de clase
    Route::prefix('class_types')->group(function () {
      Route::get('/', [ClassTypeController::class, 'index']);
      Route::get('/{class_type_id}', [ClassTypeController::class, 'show']);
      Route::post('/', [ClassTypeController::class, 'store']);
      Route::patch('/{class_type_id}', [ClassTypeController::class, 'update']);
      Route::delete('/{class_type_id}', [ClassTypeController::class, 'destroy']);
    });


    // Rutas de clases Reales
    Route::prefix('real_classes')->group(function () {
      Route::get('/', [RealClassController::class, 'index']);
      Route::get('/{real_class_id}', [RealClassController::class, 'show']);
      Route::post('/', [RealClassController::class, 'store']);
      Route::patch('/{real_class_id}', [RealClassController::class, 'update']);
      Route::delete('/{real_class_id}', [RealClassController::class, 'destroy']);
    });


    //Rutas de estados de asistencia
    Route::prefix('attendance_statuses')->group(function () {
      Route::get('/', [AttendanceStatusController::class, 'index']);
      Route::get('/{attendance_status_id}', [AttendanceStatusController::class, 'show']);
      Route::post('/', [AttendanceStatusController::class, 'store']);
      Route::patch('/{attendance_status_id}', [AttendanceStatusController::class, 'update']);
      Route::delete('/{attendance_status_id}', [AttendanceStatusController::class, 'destroy']);
    });


    //Rutas de asistencias
    Route::prefix('attendances')->group(function () {
      Route::get('/', [AttendanceController::class, 'index']);
      Route::get('/{attendance_id}', [AttendanceController::class, 'show']);
      Route::post('/', [AttendanceController::class, 'store']);
      Route::patch('/{attendance_id}', [AttendanceController::class, 'update']);
      Route::delete('/{attendance_id}', [AttendanceController::class, 'destroy']);

      Route::get('/class/{real_class_id}', [AttendanceController::class, 'byClassRealId']);
    });


    //Rutas de tipos de notificación
    Route::prefix('notification-types')->group(function () {
      Route::get('/', [NotificationTypeController::class, 'index']);
      Route::get('/{notification_type_id}', [NotificationTypeController::class, 'show']);
      Route::post('/', [NotificationTypeController::class, 'store']);
      Route::patch('/{notification_type_id}', [NotificationTypeController::class, 'update']);
      Route::delete('/{notification_type_id}', [NotificationTypeController::class, 'destroy']);
    });
  });
});
