<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('inventories', [InventoryController::class, 'index']);
    Route::get('inventories/{id}', [InventoryController::class, 'show']);
    Route::get('inventories/{id}/assets', [InventoryController::class, 'assets']);
    Route::get('inventories/{id}/constats', [InventoryController::class, 'constatsList']);
    Route::get('inventories/{id}/rapport', fn ($id) => app(InventoryController::class)->show($id));

    Route::middleware('permission:inventaire.gerer')->group(function () {
        Route::post('inventories', [InventoryController::class, 'store']);
        Route::post('inventories/{id}/start', [InventoryController::class, 'start']);
        Route::post('inventories/{id}/close', [InventoryController::class, 'close']);
    });
    Route::middleware('permission:inventaire.saisir')->post('inventories/{id}/constats', [InventoryController::class, 'constats']);
});
