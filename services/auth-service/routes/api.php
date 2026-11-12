<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Sante (public)
Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

// Authentification (public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Routes protegees JWT
Route::middleware(['jwt', 'throttle:api'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/2fa/setup', [AuthController::class, 'twoFactorSetup']);
        Route::post('/2fa/enable', [AuthController::class, 'twoFactorEnable']);
    });

    // Utilisateurs - administration
    Route::middleware('permission:utilisateur.gerer')->group(function () {
        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    });

    // Roles et permissions
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('permissions', [RoleController::class, 'permissions']);
    Route::middleware('permission:role.gerer')->group(function () {
        Route::post('roles', [RoleController::class, 'store']);
        Route::put('roles/{id}', [RoleController::class, 'update']);
    });
});
