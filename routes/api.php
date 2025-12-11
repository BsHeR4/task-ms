<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware(['throttle:5,5']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,5');

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('tasks', TaskController::class);
    });
});
