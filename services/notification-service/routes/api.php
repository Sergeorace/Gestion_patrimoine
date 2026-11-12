<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/up', fn () => response()->json(['status' => 'ok']));

// Endpoint interne pour les autres services (SERVICE_TOKEN controle en pratique)
Route::post('notifications', [NotificationController::class, 'store'])
    ->middleware('jwt');

Route::middleware(['jwt', 'throttle:api'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
});
