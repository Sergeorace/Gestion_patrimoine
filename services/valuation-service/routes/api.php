<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ValuationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('valorisation', [ValuationController::class, 'index']);
    Route::get('valorisation/stats', [ValuationController::class, 'stats']);
    Route::get('valorisation/{bienId}', [ValuationController::class, 'show']);

    Route::middleware('permission:valorisation.lire')->group(function () {
        Route::post('valorisation/calculate', [ValuationController::class, 'calculate']);
    });
    Route::middleware('permission:valorisation.gerer')->post('valorisation/reevaluations', [ValuationController::class, 'reevaluation']);
});
