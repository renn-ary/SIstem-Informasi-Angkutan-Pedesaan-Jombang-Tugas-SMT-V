<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AngkutanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransaksiController;
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

// UC-06: Kelola Transaksi Pemesanan (Penumpang).
Route::middleware(['auth', 'role:penumpang'])->group(function () {
    Route::get('transaksis', [TransaksiController::class, 'index'])->name('transaksis.index');
    Route::get('transaksis/{booking}', [TransaksiController::class, 'show'])->name('transaksis.show');
    Route::post('transaksis/{booking}/pembayaran', [TransaksiController::class, 'bayar'])->name('transaksis.bayar');
    Route::post('transaksis/{booking}/pembatalan', [TransaksiController::class, 'batalkan'])->name('transaksis.batalkan');
});

// UC-07: Pelaporan Layanan Angkutan (Masyarakat/Penumpang mengirim, Admin menindaklanjuti).
Route::middleware(['auth', 'role:penumpang'])->group(function () {
    Route::get('reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
});

Route::middleware(['auth', 'role:penumpang,admin'])->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::put('reports/{report}/tindak-lanjut', [ReportController::class, 'tindakLanjut'])->name('reports.tindak-lanjut');
});


require __DIR__.'/auth.php';
