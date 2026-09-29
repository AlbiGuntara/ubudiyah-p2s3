<?php

use App\Http\Controllers\AsramaController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaerahController;
use App\Http\Controllers\DaftarPelanggaranController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\PembinaanController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SuratPanggilanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoicePelanggaranController;
use Illuminate\Support\Facades\Route;

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
    Route::post('daerah/bulk-delete', [DaerahController::class, 'bulkDelete'])->name('daerah.bulk-delete');
    Route::resource('asrama', AsramaController::class)->except(['show', 'create', 'edit']);
    Route::post('asrama/bulk-delete', [AsramaController::class, 'bulkDelete'])->name('asrama.bulk-delete');
    Route::get('santri/cek-nis', [SantriController::class, 'cekNis'])->name('santri.cek-nis');
    Route::resource('santri', SantriController::class)->except(['create', 'edit']);
    Route::post('santri/import', [SantriController::class, 'import'])->name('santri.import');
    Route::post('santri/bulk-delete', [SantriController::class, 'bulkDelete'])->name('santri.bulk-delete');
    Route::resource('petugas', PetugasController::class)
        ->parameters(['petugas' => 'petugas'])
        ->except(['show', 'create', 'edit']);
    Route::post('petugas/bulk-delete', [PetugasController::class, 'bulkDelete'])->name('petugas.bulk-delete');
    Route::resource('daftar-pelanggaran', DaftarPelanggaranController::class)->except(['show', 'create', 'edit']);
    Route::post('daftar-pelanggaran/bulk-delete', [DaftarPelanggaranController::class, 'bulkDelete'])->name('daftar-pelanggaran.bulk-delete');

    // Pelanggaran
    Route::resource('pelanggaran', PelanggaranController::class)->except(['create', 'edit', 'show']);
    Route::post('pelanggaran/massal', [PelanggaranController::class, 'storeMassal'])->name('pelanggaran.massal');
    Route::post('pelanggaran/bulk-delete', [PelanggaranController::class, 'bulkDelete'])->name('pelanggaran.bulk-delete');
    Route::post('pelanggaran/bulk-update', [PelanggaranController::class, 'bulkUpdate'])->name('pelanggaran.bulk-update');
    Route::post('pelanggaran/voice', [VoicePelanggaranController::class, 'store'])
        ->middleware('throttle:'.config('voice.throttle.transcribe'))
        ->name('pelanggaran.voice');

    // Surat Panggilan
    Route::get('pelanggaran/surat-panggilan/cetak', [SuratPanggilanController::class, 'cetak'])->name('pelanggaran.surat-panggilan.cetak');
    Route::get('pelanggaran/surat-panggilan/riwayat', [SuratPanggilanController::class, 'riwayat'])->name('pelanggaran.surat-panggilan.riwayat');
    Route::get('pelanggaran/surat-panggilan/riwayat-global', [SuratPanggilanController::class, 'riwayatGlobal'])->name('pelanggaran.surat-panggilan.riwayat-global');
    Route::get('pelanggaran/surat-panggilan/{suratPanggilan}/cetak-ulang', [SuratPanggilanController::class, 'cetakUlang'])->name('pelanggaran.surat-panggilan.cetak-ulang');
    Route::post('pelanggaran/surat-panggilan/delete-session', [SuratPanggilanController::class, 'destroySession'])->name('pelanggaran.surat-panggilan.delete-session');
    // Pembinaan
    Route::get('pembinaan', [PembinaanController::class, 'index'])->name('pembinaan.index');
    Route::put('pembinaan/{pembinaan}', [PembinaanController::class, 'update'])->name('pembinaan.update');
    Route::post('pembinaan/{pembinaan}/setor-sanksi', [PembinaanController::class, 'setorSanksi'])->name('pembinaan.setor-sanksi');
    Route::post('pembinaan/{pembinaan}/tambah-sanksi', [PembinaanController::class, 'tambahSanksi'])->name('pembinaan.tambah-sanksi');
    Route::post('pembinaan/pemutihan', [PembinaanController::class, 'pemutihan'])->name('pembinaan.pemutihan');
    Route::get('pembinaan/cetak', [PembinaanController::class, 'cetak'])->name('pembinaan.cetak');
    Route::get('pembinaan/{pembinaan}/pelanggaran', [PembinaanController::class, 'getPelanggaran'])->name('pembinaan.pelanggaran');

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('bulanan', [LaporanController::class, 'bulanan'])->name('bulanan');
        Route::get('tahunan', [LaporanController::class, 'tahunan'])->name('tahunan');
        Route::get('total', [LaporanController::class, 'total'])->name('total');
        Route::get('per-daerah', [LaporanController::class, 'perDaerah'])->name('per-daerah');
        Route::get('per-asrama', [LaporanController::class, 'perAsrama'])->name('per-asrama');
    });

    // Export
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('excel', [ExportController::class, 'excelKomprehensif'])->name('excel');
        Route::get('excel/{section}', [ExportController::class, 'excelSection'])->name('excel.section');
        Route::get('bulanan/excel', [ExportController::class, 'excelBulanan'])->name('bulanan.excel');
        Route::get('tahunan/excel', [ExportController::class, 'excelTahunan'])->name('tahunan.excel');
        Route::get('bulanan/pdf', [ExportController::class, 'pdfBulanan'])->name('bulanan.pdf');
        Route::get('tahunan/pdf', [ExportController::class, 'pdfTahunan'])->name('tahunan.pdf');
        Route::get('pelanggaran-full/pdf', [ExportController::class, 'pdfPelanggaranFull'])->name('export.pelanggaran-full.pdf');
    });

    // Search
    Route::get('search', SearchController::class)->name('search');

    // Audit Log
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

    // User Management (super_admin only)
    Route::middleware('super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);
        Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
        Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
        Route::resource('permissions', PermissionController::class)->except(['show', 'create', 'edit']);
    });
});
