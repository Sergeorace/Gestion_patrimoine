<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('documents', [DocumentController::class, 'index']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);
    Route::get('documents/{id}/download', [DocumentController::class, 'download']);

    Route::middleware('permission:document.gerer')->group(function () {
        Route::post('documents', [DocumentController::class, 'store']);
        Route::post('documents/{id}/versions', [DocumentController::class, 'newVersion']);
        Route::delete('documents/{id}', [DocumentController::class, 'destroy']);
    });
});
