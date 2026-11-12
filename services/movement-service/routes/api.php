<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MovementController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('movements', [MovementController::class, 'index']);
    Route::get('movements/{id}', [MovementController::class, 'show']);
    Route::get('movements/{id}/history', [MovementController::class, 'history']);
    Route::get('motifs-sortie', [MovementController::class, 'motifs']);

    Route::middleware('permission:mouvement.creer')->group(function () {
        Route::post('movements', [MovementController::class, 'store']);
        Route::post('movements/{id}/cancel', [MovementController::class, 'cancel']);
    });
    Route::middleware('permission:mouvement.valider')->post('movements/{id}/decision', [MovementController::class, 'decide']);
});
