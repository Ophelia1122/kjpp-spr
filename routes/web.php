<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// GUEST — belum login
// =========================================================================
Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.attempt');
    });

// =========================================================================
// AUTHENTICATED — sudah login (middleware 'active' sudah global lewat
// bootstrap/app.php, tidak perlu ditulis ulang di sini)
// =========================================================================
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Ganti password sendiri — SENGAJA di luar semua grup middleware
    // 'permission:...', supaya semua role bisa akses tanpa terkecuali.
    Route::get('/profile/password', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // --- Dashboard ---
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    Route::middleware('permission:reports.export')
        ->get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.exportExcel');

    // --- Klien: pencarian AJAX (dipakai di dalam form proposal, hanya butuh 'view') ---
    Route::middleware('permission:clients.view')->get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    // --- Klien: tambah lewat modal (aksi 'manage') ---
    Route::middleware('permission:clients.manage')->post('/clients/store-ajax', [ClientController::class, 'storeAjax'])->name('clients.storeAjax');

    // --- Klien: manajemen data ---
    Route::middleware('permission:clients.view')->get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::middleware('permission:clients.manage')->group(function () {
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    
    // --- Proposal: kelola (buat/edit/hapus) ---
    Route::middleware('permission:proposals.manage')->group(function () {
        Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
        Route::get('/proposals/{project}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
        Route::put('/proposals/{project}', [ProposalController::class, 'update'])->name('proposals.update');
        Route::delete('/proposals/{project}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
        Route::post('/proposals/{project}/approve', [ProposalController::class, 'markApproved'])->name('proposals.markApproved');
    });
    // --- Proposal: lihat ---
    Route::middleware('permission:proposals.view')->group(function () {
        Route::get('/proposals/{project}', [ProjectController::class, 'show'])->name('proposals.show');
        Route::get('/proposals/{project}/pdf', [ProposalController::class, 'exportPdf'])->name('proposals.exportPdf');
        Route::get('/proposals/{project}/word', [ProposalController::class, 'exportWord'])->name('proposals.exportWord');
    });

    // --- Survei Lapangan: lihat (termasuk cetak Surat Tugas — dokumen hasil, bukan aksi ubah data) ---
    Route::middleware('permission:survey.view')
        ->get('/projects/{project}/surat-tugas', [ProjectController::class, 'exportSuratTugas'])->name('projects.exportSuratTugas');
    // --- Survei Lapangan: kelola (input data) ---
    Route::middleware('permission:survey.manage')->group(function () {
        Route::post('/projects/{project}/survey-data', [ProjectController::class, 'inputSurveyData'])->name('projects.inputSurveyData');
        Route::post('/projects/{project}/final-report-number', [ProjectController::class, 'inputFinalReportNumber'])->name('projects.inputFinalReportNumber');
    });

    // --- Invoice: lihat (termasuk cetak PDF invoice/kwitansi) ---
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'exportInvoice'])->name('invoices.exportInvoice');
        Route::get('/invoices/{invoice}/kwitansi', [InvoiceController::class, 'exportKwitansi'])->name('invoices.exportKwitansi');
    });
    // --- Invoice: kelola (terbitkan/verifikasi/batalkan) ---
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::post('/projects/{project}/invoices/dp', [InvoiceController::class, 'generateDp'])->name('invoices.generateDp');
        Route::post('/projects/{project}/invoices/final', [InvoiceController::class, 'generateFinal'])->name('invoices.generateFinal');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.markAsPaid');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    // --- Pengaturan Sistem: Role & User Management (khusus izin 'roles.manage' / 'users.manage',
    //     yang secara default HANYA dimiliki Administrator) ---
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles', [RoleController::class, 'update'])->name('roles.update');
        
    });
    Route::middleware('permission:audit.view')
        ->get('/audit-log', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit.index');


    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
