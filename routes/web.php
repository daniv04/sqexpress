<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'isAdmin'])->name('dashboard');

Route::get('/panel', [PanelController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('panel');

Route::get('/mis-paquetes', [PackageController::class, 'userPackages'])
    ->middleware(['auth', 'verified'])->name('mis-paquetes');

Route::get('/mis-paquetes/crear', [PackageController::class, 'create'])
    ->middleware(['auth', 'verified'])->name('package.create');

Route::post('/mis-paquetes', [PackageController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('package.store');

Route::get('/mis-paquetes/{package}', [PackageController::class, 'show'])
    ->middleware(['auth', 'verified'])->name('package.show');

Route::get('/mis-paquetes/{package}/editar', [PackageController::class, 'edit'])
    ->middleware(['auth', 'verified'])->name('package.edit');

Route::put('/mis-paquetes/{package}', [PackageController::class, 'update'])
    ->middleware(['auth', 'verified'])->name('package.update');

Route::delete('/mis-paquetes/{package}', [PackageController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('package.destroy');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/phone', [ProfileController::class, 'updatePhone'])->name('profile.update-phone');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
