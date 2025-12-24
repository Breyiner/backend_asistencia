<?php

use App\Http\Controllers\API\UserStatus\UserStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/prueba', function (Request $request) {
    return response()->json(['message' => 'La API está funcionando correctamente.'], 200);
});

  Route::prefix('user_statuses')->group(function () {
    Route::get('/', [UserStatusController::class, 'index']);
    Route::get('/{status_id}', [UserStatusController::class, 'show']);
    Route::post('/', [UserStatusController::class, 'store']);
    Route::put('/{status_id}', [UserStatusController::class, 'update']);
    Route::patch('/{status_id}', [UserStatusController::class, 'partialUpdate']);
    Route::delete('/{status_id}', [UserStatusController::class, 'destroy']);
  });
