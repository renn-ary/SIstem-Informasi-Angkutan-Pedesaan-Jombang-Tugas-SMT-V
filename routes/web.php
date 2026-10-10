<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AngkutanController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $dashboardRole = match (auth()->user()->role) {
        'penumpang' => 'penumpang',
        'pengemudi' => 'pengemudi',
        'admin' => 'admin',
        'super_admin' => 'super-admin',
        default => abort(403),
    };

    return Inertia::render('Dashboard', ['dashboardRole' => $dashboardRole]);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {

    Route::resource('users', UserController::class);

});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('angkutans', AngkutanController::class);
});


require __DIR__.'/auth.php';
