<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DaerahController;
use App\Http\Controllers\AsramaController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\DaftarPelanggaranController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\PembinaanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AuditLogController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::post('login', [AuthController::class, 'authenticate']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('appearance', [AuthController::class, 'toggleAppearance'])->name('appearance.toggle');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::resource('daerah', DaerahController::class)->except(['show', 'create', 'edit']);
    Route::resource('asrama', AsramaController::class)->except(['show', 'create', 'edit']);
    Route::resource('santri', SantriController::class)->except(['create', 'edit']);
    Route::post('santri/import', [SantriController::class, 'import'])->name('santri.import');
    Route::resource('petugas', PetugasController::class)->except(['show', 'create', 'edit']);
    Route::resource('daftar-pelanggaran', DaftarPelanggaranController::class)->except(['show', 'create', 'edit']);

    // Pelanggaran
    Route::resource('pelanggaran', PelanggaranController::class)->except(['create', 'edit', 'show']);
    Route::post('pelanggaran/massal', [PelanggaranController::class, 'storeMassal'])->name('pelanggaran.massal');

    // Pembinaan
    Route::resource('pembinaan', PembinaanController::class)->except(['show', 'create', 'edit']);

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('bulanan', [LaporanController::class, 'bulanan'])->name('bulanan');
        Route::get('tahunan', [LaporanController::class, 'tahunan'])->name('tahunan');
        Route::get('total', [LaporanController::class, 'total'])->name('total');
        Route::get('per-daerah', [LaporanController::class, 'perDaerah'])->name('per-daerah');
        Route::get('per-asrama', [LaporanController::class, 'perAsrama'])->name('per-asrama');
    });

    // Export
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('bulanan/excel', [ExportController::class, 'excelBulanan'])->name('bulanan.excel');
        Route::get('tahunan/excel', [ExportController::class, 'excelTahunan'])->name('tahunan.excel');
        Route::get('bulanan/pdf', [ExportController::class, 'pdfBulanan'])->name('bulanan.pdf');
        Route::get('tahunan/pdf', [ExportController::class, 'pdfTahunan'])->name('tahunan.pdf');
    });

    // Search
    Route::get('search', SearchController::class)->name('search');

    // Audit Log
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
});
