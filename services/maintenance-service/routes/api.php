<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('maintenance/plans', [MaintenanceController::class, 'plans']);
    Route::get('maintenance/interventions', [MaintenanceController::class, 'interventions']);
    Route::get('maintenance/interventions/{id}', [MaintenanceController::class, 'show']);
    Route::get('maintenance/prestataires', [MaintenanceController::class, 'prestataires']);

    Route::middleware('permission:maintenance.gerer')->group(function () {
        Route::post('maintenance/plans', [MaintenanceController::class, 'storePlan']);
        Route::put('maintenance/plans/{id}', [MaintenanceController::class, 'updatePlan']);
        Route::post('maintenance/pannes', [MaintenanceController::class, 'declarePanne']);
        Route::post('maintenance/interventions', [MaintenanceController::class, 'storeIntervention']);
        Route::post('maintenance/interventions/{id}/start', [MaintenanceController::class, 'start']);
        Route::post('maintenance/interventions/{id}/close', [MaintenanceController::class, 'close']);
        Route::post('maintenance/prestataires', [MaintenanceController::class, 'storePrestataire']);
    });
});
