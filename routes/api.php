<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\API\Area\AreaController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DocumentType\DocumentTypeController;
use App\Http\Controllers\API\EmailVerification\EmailVerificationController;
use App\Http\Controllers\API\FichaStatus\FichaStatusController;
use App\Http\Controllers\API\QualificationLevel\QualificationLevelController;
use App\Http\Controllers\API\Role\RoleController;
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
  });
});
