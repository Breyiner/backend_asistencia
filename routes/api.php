<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DocumentType\DocumentTypeController;
use App\Http\Controllers\API\EmailVerification\EmailVerificationController;
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
  });
});
