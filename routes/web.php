<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SurveyReportController;
use App\Http\Controllers\TrashController;
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
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::put('/profile/theme', [\App\Http\Controllers\ProfileController::class, 'toggleTheme'])->name('profile.toggleTheme');

    // --- Dashboard ---
    // "Beranda"           = dashboard operasional SEMUA role        -> home()
    // "Dashboard Project" = tabel daftar proyek (route lama 'dashboard') -> index()
    // "Timeline Project"  = Gantt mini SLA per proyek               -> TimelineController
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/', [DashboardController::class, 'home'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/timeline', [\App\Http\Controllers\TimelineController::class, 'index'])->name('timeline');
    });

    // "Dashboard" = ringkasan manajemen (nilai kontrak, pipeline, grafik).
    // Izin TERPISAH supaya Surveyor tidak melihat angka kontrak.
    Route::middleware('permission:dashboard.overview')
        ->get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');

    // "Dashboard Pembayaran" — rekap semua Invoice/DP/Kwitansi lintas
    // proyek + sisa tagihan per proyek. Sama seperti Invoice, izinnya
    // 'invoices.view' (Surveyor tidak punya, sesuai akses invoice lain).
    Route::middleware('permission:invoices.view')
        ->get('/dashboard/pembayaran', [\App\Http\Controllers\PaymentDashboardController::class, 'index'])->name('dashboard.pembayaran');
    // Atur "nomor terakhir" Invoice & Kwitansi (ikon pengaturan, 2026-09-14).
    Route::middleware('permission:invoices.manage')
        ->put('/dashboard/pembayaran/penomoran', [\App\Http\Controllers\PaymentDashboardController::class, 'updateNumbering'])->name('dashboard.pembayaran.numbering');
    Route::middleware('permission:reports.export')
        ->get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.exportExcel');

    // Palet pencarian cepat Ctrl+K (2026-09-25).
    Route::middleware('permission:proposals.view')
        ->get('/quick-search', [DashboardController::class, 'quickSearch'])->name('quickSearch');

    // --- Klien: pencarian AJAX (dipakai di dalam form proposal, hanya butuh 'view') ---
    Route::middleware('permission:clients.view')->get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    // Peringatan klien kembar di modal Tambah Klien Baru (2026-09-15).
    Route::middleware('permission:clients.view')->get('/clients/similar', [ClientController::class, 'similar'])->name('clients.similar');
    // --- Klien: tambah lewat modal (aksi 'manage') ---
    Route::middleware('permission:clients.manage')->post('/clients/store-ajax', [ClientController::class, 'storeAjax'])->name('clients.storeAjax');

    // --- Klien: manajemen data ---
    // Sampah: proyek & klien yang dihapus, bisa dipulihkan 30 hari (2026-09-20).
    Route::middleware('permission:proposals.manage')->group(function () {
        Route::get('/sampah', [TrashController::class, 'index'])->name('trash.index');
        Route::post('/sampah/proyek/{id}/pulihkan', [TrashController::class, 'restoreProject'])->whereNumber('id')->name('trash.projects.restore');
        Route::delete('/sampah/proyek/{id}', [TrashController::class, 'forceDeleteProject'])->whereNumber('id')->name('trash.projects.forceDelete');
        Route::post('/sampah/klien/{id}/pulihkan', [TrashController::class, 'restoreClient'])->whereNumber('id')->name('trash.clients.restore');
        Route::delete('/sampah/klien/{id}', [TrashController::class, 'forceDeleteClient'])->whereNumber('id')->name('trash.clients.forceDelete');
    });

    // SPJ Surveyor — rekap survei per penilai (2026-09-19, feedback user).
    Route::middleware('permission:survey.view')->get('/spj-surveyor', [SurveyReportController::class, 'index'])->name('spj.index');

    Route::middleware('permission:clients.view')->get('/clients', [ClientController::class, 'index'])->name('clients.index');
    // Detail klien + proyek yang melibatkannya (2026-09-15).
    Route::middleware('permission:clients.view')->get('/clients/{client}', [ClientController::class, 'show'])
        ->whereNumber('client')->name('clients.show');
    Route::middleware('permission:clients.manage')->group(function () {
        Route::get('/clients-baru', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    
    // --- Proposal: kelola (buat/edit/hapus) ---
    Route::middleware('permission:proposals.manage')->group(function () {
        Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
        Route::get('/proposals/{project}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
        // Duplikat proposal untuk klien langganan (2026-09-24).
        Route::post('/proposals/{project}/duplicate', [ProposalController::class, 'duplicate'])->name('proposals.duplicate');
        // Batal tepat setelah duplikat: salinan dibuang permanen.
        Route::delete('/proposals/{project}/duplicate', [ProposalController::class, 'discardDuplicate'])->name('proposals.discardDuplicate');
        Route::put('/proposals/{project}', [ProposalController::class, 'update'])->name('proposals.update');
        Route::delete('/proposals/{project}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
        // Draft -> Menunggu Persetujuan Klien (2026-09-15).
        Route::post('/proposals/{project}/send-to-client', [ProposalController::class, 'markSentToClient'])->name('proposals.markSentToClient');

        // --- Proposal: Batal / Aktifkan kembali (Batch 7) ---
        // Non-destruktif: data proyek tetap utuh, hanya status yang berubah.
        Route::post('/proposals/{project}/cancel', [ProposalController::class, 'cancel'])->name('proposals.cancel');
        // Batalkan beberapa proyek sekaligus dari List Project (2026-09-24).
        Route::post('/proposals/cancel-many', [ProposalController::class, 'cancelMany'])->name('proposals.cancelMany');
        // Urutan petugas Surat Tugas diubah dengan digeser (2026-09-24).
        Route::post('/projects/{project}/assignment-staff/reorder', [ProjectController::class, 'reorderAssignmentStaff'])->name('projects.assignmentStaff.reorder');
        // Urungkan penghapusan petugas (2026-09-25).
        Route::post('/projects/{project}/assignment-staff/{user}/undo', [ProjectController::class, 'undoRemoveAssignmentStaff'])->name('projects.assignmentStaff.undo');
        Route::post('/proposals/{project}/reactivate', [ProposalController::class, 'reactivate'])->name('proposals.reactivate');

        // --- Skema "Bayar Nanti": mulai kerja lapangan tanpa DP (2026-09-14) ---
        Route::post('/projects/{project}/start-without-dp', [ProjectController::class, 'startWorkWithoutDp'])->name('projects.startWithoutDp');

        // --- Proposal: editor teks baku per-bab (override; Batch 3) ---
        Route::get('/proposals/{project}/texts', [ProposalController::class, 'editTexts'])->name('proposals.texts');
        Route::put('/proposals/{project}/texts/{key}', [ProposalController::class, 'updateText'])->name('proposals.texts.update');
        Route::delete('/proposals/{project}/texts/{key}', [ProposalController::class, 'resetText'])->name('proposals.texts.reset');
        Route::delete('/proposals/{project}/texts', [ProposalController::class, 'resetAllTexts'])->name('proposals.texts.resetAll');
    });

    // --- Proposal: Nomor & Tanggal Faktur Pajak (Feature 6 — Administrator + Admin Keuangan) ---
    Route::middleware('permission:tax_invoice.manage')
        ->put('/proposals/{project}/tax-invoice', [ProposalController::class, 'updateTaxInvoice'])->name('proposals.taxInvoice');
    // --- Proposal: lihat ---
    Route::middleware('permission:proposals.view')->group(function () {
        Route::get('/proposals/{project}', [ProjectController::class, 'show'])->name('proposals.show');
        Route::get('/proposals/{project}/lengkap', [ProjectController::class, 'lengkap'])->name('proposals.lengkap');
        Route::get('/proposals/{project}/pdf', [ProposalController::class, 'exportPdf'])->name('proposals.exportPdf');
        Route::get('/proposals/{project}/word', [ProposalController::class, 'exportWord'])->name('proposals.exportWord');
        Route::get('/proposals/{project}/representatif', [ProposalController::class, 'exportRepresentatif'])->name('proposals.exportRepresentatif');
        // Tanda Terima Pengiriman Buku (2026-09-23)
        Route::get('/proposals/{project}/tanda-terima/{receipt}/pdf', [\App\Http\Controllers\DeliveryReceiptController::class, 'exportPdf'])->name('receipts.pdf');
        Route::get('/proposals/{project}/tanda-terima/{receipt}/word', [\App\Http\Controllers\DeliveryReceiptController::class, 'exportWord'])->name('receipts.word');
        Route::post('/proposals/{project}/tanda-terima', [\App\Http\Controllers\DeliveryReceiptController::class, 'store'])->name('receipts.store');
        Route::delete('/proposals/{project}/tanda-terima/{receipt}', [\App\Http\Controllers\DeliveryReceiptController::class, 'destroy'])->name('receipts.destroy');
    });

    // --- Survei Lapangan: lihat (termasuk cetak Surat Tugas — dokumen hasil, bukan aksi ubah data) ---
    Route::middleware('permission:survey.view')
        ->get('/projects/{project}/surat-tugas', [ProjectController::class, 'exportSuratTugas'])->name('projects.exportSuratTugas');
    // Surat Tugas versi Word (.docx), izin sama dengan cetak PDF (2026-09-14).
    Route::middleware('permission:survey.view')
        ->get('/projects/{project}/surat-tugas/word', [ProjectController::class, 'exportSuratTugasWord'])->name('projects.exportSuratTugasWord');
    // --- Surat Tugas: isi Nomor/Tanggal/Barcode + kelola daftar petugas (Administrator & Admin Keuangan) ---
    Route::middleware('permission:assignment_letter.manage')->group(function () {
        Route::put('/projects/{project}/assignment-letter', [ProjectController::class, 'updateAssignmentLetter'])->name('projects.updateAssignmentLetter');
        Route::post('/projects/{project}/assignment-letter/staff', [ProjectController::class, 'addAssignmentStaff'])->name('projects.assignmentStaff.store');
        Route::delete('/projects/{project}/assignment-letter/staff/{staff}', [ProjectController::class, 'removeAssignmentStaff'])->name('projects.assignmentStaff.destroy');
        Route::post('/projects/{project}/assignment-letter/barcode', [ProjectController::class, 'uploadAssignmentLetterBarcode'])->name('projects.assignmentLetterBarcode.store');
        Route::delete('/projects/{project}/assignment-letter/barcode', [ProjectController::class, 'deleteAssignmentLetterBarcode'])->name('projects.assignmentLetterBarcode.destroy');
    });
    // --- Survei Lapangan: kelola (input data) ---
    Route::middleware('permission:survey.manage')->group(function () {
        Route::post('/projects/{project}/survey-data', [ProjectController::class, 'inputSurveyData'])->name('projects.inputSurveyData');
    });

    // --- Nomor Laporan Resmi: Admin Produksi & Admin Keuangan (2026-09-14,
    //     feedback user — BUKAN Surveyor, beda dari data survei lapangan) ---
    Route::middleware('permission:final_report.manage')
        ->post('/projects/{project}/final-report-number', [ProjectController::class, 'inputFinalReportNumber'])->name('projects.inputFinalReportNumber');

    // --- Alur produksi laporan (2026-09-15) — satu pintu untuk semua tombol
    //     perpindahan peran. Hak akses per langkah (Surveyor / Reviewer /
    //     Admin Produksi) dicek di controller, lihat Project::WORKFLOW_STEPS.
    Route::post('/projects/{project}/workflow/{step}', [ProjectController::class, 'advanceWorkflow'])
        ->where('step', '[a-z_]+')->name('projects.workflow');

    // --- Invoice: lihat (termasuk cetak PDF invoice/kwitansi) ---
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'exportInvoice'])->name('invoices.exportInvoice');
        Route::get('/invoices/{invoice}/kwitansi', [InvoiceController::class, 'exportKwitansi'])->name('invoices.exportKwitansi');
        // Versi Word (.docx) untuk isi yang perlu disesuaikan manual (2026-09-14).
        Route::get('/invoices/{invoice}/word', [InvoiceController::class, 'exportInvoiceWord'])->name('invoices.exportInvoiceWord');
        Route::get('/invoices/{invoice}/kwitansi/word', [InvoiceController::class, 'exportKwitansiWord'])->name('invoices.exportKwitansiWord');
    });
    // --- Invoice: kelola (terbitkan/verifikasi/batalkan) ---
    // "invoices.store" = SATU pintu terbit invoice, fleksibel berapa
    // kali/termin (menggantikan generateDp/generateFinal lama).
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::post('/projects/{project}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.markAsPaid');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    // --- Pengaturan Sistem: Role & User Management (khusus izin 'roles.manage' / 'users.manage',
    //     yang secara default HANYA dimiliki Administrator) ---
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles', [RoleController::class, 'update'])->name('roles.update');
        
    });
    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit-log', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit.index');
        // Unduh log sesuai filter aktif (2026-09-24).
        Route::get('/audit-log/export', [\App\Http\Controllers\AuditLogController::class, 'export'])->name('audit.export');
        // Mengosongkan log: Administrator saja, diverifikasi kata sandi di controller.
        Route::post('/audit-log/clear', [\App\Http\Controllers\AuditLogController::class, 'clear'])->name('audit.clear');
    });

    // --- Pengaturan Sistem: Master Rekening Bank (izin 'banks.manage', default hanya Administrator) ---
    Route::middleware('permission:banks.manage')->group(function () {
        Route::get('/banks', [\App\Http\Controllers\BankController::class, 'index'])->name('banks.index');
        Route::get('/banks/create', [\App\Http\Controllers\BankController::class, 'create'])->name('banks.create');
        Route::post('/banks', [\App\Http\Controllers\BankController::class, 'store'])->name('banks.store');
        Route::get('/banks/{bank}/edit', [\App\Http\Controllers\BankController::class, 'edit'])->name('banks.edit');
        Route::put('/banks/{bank}', [\App\Http\Controllers\BankController::class, 'update'])->name('banks.update');
        Route::delete('/banks/{bank}', [\App\Http\Controllers\BankController::class, 'destroy'])->name('banks.destroy');
    });


    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Bot notifikasi WhatsApp (2026-09-14)
        Route::get('/settings/whatsapp', [\App\Http\Controllers\WhatsAppSettingsController::class, 'edit'])->name('settings.whatsapp.edit');
        Route::put('/settings/whatsapp', [\App\Http\Controllers\WhatsAppSettingsController::class, 'update'])->name('settings.whatsapp.update');
        Route::get('/settings/whatsapp/groups', [\App\Http\Controllers\WhatsAppSettingsController::class, 'groups'])->name('settings.whatsapp.groups');
        Route::post('/settings/whatsapp/test', [\App\Http\Controllers\WhatsAppSettingsController::class, 'test'])->name('settings.whatsapp.test');
        Route::put('/settings/whatsapp/notifications', [\App\Http\Controllers\WhatsAppSettingsController::class, 'updateNotifications'])->name('settings.whatsapp.notifications');
        Route::post('/settings/whatsapp/notifications/{step}/test', [\App\Http\Controllers\WhatsAppSettingsController::class, 'testNotification'])->name('settings.whatsapp.notifications.test');
    });
});
