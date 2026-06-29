<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MovimientoBancarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\RentaController;
use App\Http\Controllers\SeguroController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('propiedades', PropiedadController::class)
        ->parameters(['propiedades' => 'propiedade'])
        ->except('show');
    Route::resource('seguros', SeguroController::class)->except('show');
    Route::resource('rentas', RentaController::class)->except('show');
    Route::resource('movimientos', MovimientoBancarioController::class)
        ->parameters(['movimientos' => 'movimiento'])
        ->except('show');
});

require __DIR__.'/auth.php';
