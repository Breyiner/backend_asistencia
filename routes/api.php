<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\EmailVerification\EmailVerificationController;
use App\Http\Controllers\API\UserStatus\UserStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/prueba', function (Request $request) {
  return response()->json(['message' => 'La API está funcionando correctamente.'], 200);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
  ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
  ->middleware('signed')
  ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
  ->middleware(['throttle:6,1'])
  ->name('verification.send');


Route::middleware(['auth:sanctum', 'verified'])->group(function () {

  Route::prefix('user_statuses')->group(function () {
    Route::get('/', [UserStatusController::class, 'index']);
    Route::get('/{status_id}', [UserStatusController::class, 'show']);
    Route::post('/', [UserStatusController::class, 'store']);
    Route::put('/{status_id}', [UserStatusController::class, 'update']);
    Route::patch('/{status_id}', [UserStatusController::class, 'partialUpdate']);
    Route::delete('/{status_id}', [UserStatusController::class, 'destroy']);
  });
});
