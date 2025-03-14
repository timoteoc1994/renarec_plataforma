<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ViewAsociationController;
use App\Http\Controllers\CityController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/asociation', [ViewAsociationController::class, 'index'])->name('asociation.index');
    Route::get('/asociation/show/{id}', [ViewAsociationController::class, 'show'])->name('asociation.index.show');
    Route::patch('/asociation/information', [ViewAsociationController::class, 'update'])->name('asociation.index.update');
    Route::delete('/asociation_delete/{deudas}', [ViewAsociationController::class, 'delete'])->name('asociation.index.delete');

    //crud ciudades
    Route::get('/ciudad', [CityController::class, 'index'])->name('ciudad.index');
    Route::post('/ciudad/nuevo', [CityController::class, 'store'])->name('ciudad.index.nuevo');
    Route::delete('/ciudad_delete/{deudas}', [CityController::class, 'delete'])->name('ciudad.index.delete');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
