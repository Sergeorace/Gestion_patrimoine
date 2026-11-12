<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('assets', [AssetController::class, 'index']);
    Route::get('assets/stats', [AssetController::class, 'stats']);
    Route::get('assets/{id}', [AssetController::class, 'show']);
    Route::get('assets/{id}/qrcode.svg', [AssetController::class, 'qrcode']);

    // Endpoints internes (appeles par movement/maintenance avec JWT de service)
    Route::patch('assets/{id}/affectation', [AssetController::class, 'affecter']);
    Route::patch('assets/{id}/etat', [AssetController::class, 'etat']);

    Route::middleware('permission:bien.creer')->post('assets', [AssetController::class, 'store']);
    Route::middleware('permission:bien.modifier')->put('assets/{id}', [AssetController::class, 'update']);
    Route::middleware('permission:bien.modifier')->delete('assets/{id}', [AssetController::class, 'destroy']);
});
