<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('web')->group(function () {
    Route::get('/connexion', fn () => Inertia::render('Login'))->name('login');
    Route::get('/', fn () => redirect('/tableau-de-bord'));
    Route::get('/tableau-de-bord', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/biens', fn () => Inertia::render('Assets/Index'))->name('assets.index');
    Route::get('/biens/nouveau', fn () => Inertia::render('Assets/Create'))->name('assets.create');
    Route::get('/biens/{id}', function (string $id) {
        return Inertia::render('Assets/Show', ['assetId' => $id]);
    })->name('assets.show');
    Route::get('/mouvements', fn () => Inertia::render('Movements/Index'))->name('movements.index');
    Route::get('/mouvements/nouveau', fn () => Inertia::render('Movements/Create'))->name('movements.create');
    Route::get('/maintenance', fn () => Inertia::render('Maintenance'))->name('maintenance.index');
    Route::get('/inventaires', fn () => Inertia::render('Inventories'))->name('inventories.index');
    Route::get('/rapports', fn () => Inertia::render('Reports'))->name('reports.index');
    Route::get('/admin', fn () => Inertia::render('Admin'))->name('admin.index');
});
