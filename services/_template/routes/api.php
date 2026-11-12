<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API du service - prefixe /api/v1/<domaine>
| Routes publiques en haut, routes protegees (JWT) ensuite.
|--------------------------------------------------------------------------
*/

// Sante (public)
Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

/*
| Routes metier - a completer par chaque service.
| Convention :
|   Route::middleware(['jwt', 'throttle:api'])->group(function () {
|       Route::get('/assets', ...);
|   });
|   Route::middleware(['jwt', 'permission:xxx', 'throttle:api'])->...
*/
