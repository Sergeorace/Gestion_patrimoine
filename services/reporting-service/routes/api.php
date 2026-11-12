<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/{id}/download', [ReportController::class, 'download']);

    Route::middleware('permission:report.gerer')->post('reports/generate', [ReportController::class, 'generate']);
});
