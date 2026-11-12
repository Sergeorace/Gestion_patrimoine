<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrganisationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

// Consultation publique authentifiee (utilisee par les autres services et le front)
Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('structures', [OrganisationController::class, 'structures']);
    Route::get('sites', [OrganisationController::class, 'sites']);
    Route::get('categories', [OrganisationController::class, 'categories']);
    Route::get('nomenclatures', [OrganisationController::class, 'nomenclatures']);
});

Route::middleware(['jwt', 'permission:referentiel.gerer', 'throttle:api'])->group(function () {
    Route::post('structures', [OrganisationController::class, 'storeStructure']);
    Route::put('structures/{id}', [OrganisationController::class, 'updateStructure']);
    Route::delete('structures/{id}', [OrganisationController::class, 'destroyStructure']);
    Route::post('sites', [OrganisationController::class, 'storeSite']);
    Route::put('sites/{id}', [OrganisationController::class, 'updateSite']);
    Route::post('categories', [OrganisationController::class, 'storeCategory']);
    Route::put('categories/{id}', [OrganisationController::class, 'updateCategory']);
    Route::post('nomenclatures', [OrganisationController::class, 'storeNomenclature']);
});
