<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::group([], function () {

    // --- Dashboard (halaman utama / taskbar masuk dari sini) ---
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.exportExcel');

    // --- Klien (dipakai untuk combobox AJAX & modal popup) ---
    Route::post('/clients/store-ajax', [ClientController::class, 'storeAjax'])->name('clients.storeAjax');
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');

    // --- Proposal ---
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals/{project}', [ProjectController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{project}/pdf', [ProposalController::class, 'exportPdf'])->name('proposals.exportPdf');
    Route::post('/proposals/{project}/approve', [ProposalController::class, 'markApproved'])->name('proposals.markApproved');

    // --- Proyek (survei & surat tugas) ---
    Route::post('/projects/{project}/survey-data', [ProjectController::class, 'inputSurveyData'])->name('projects.inputSurveyData');
    Route::get('/projects/{project}/surat-tugas', [ProjectController::class, 'exportSuratTugas'])->name('projects.exportSuratTugas');
    Route::post('/projects/{project}/draft-completed', [ProjectController::class, 'markDraftCompleted'])->name('projects.markDraftCompleted');
    Route::post('/projects/{project}/final-report-number', [ProjectController::class, 'inputFinalReportNumber'])->name('projects.inputFinalReportNumber');

    // --- Invoice ---
    Route::post('/projects/{project}/invoices/dp', [InvoiceController::class, 'generateDp'])->name('invoices.generateDp');
    Route::get('/projects/{project}/invoices/final', [InvoiceController::class, 'generateFinal'])->name('invoices.generateFinal');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.markAsPaid');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'exportInvoice'])->name('invoices.exportInvoice');
    Route::get('/invoices/{invoice}/kwitansi', [InvoiceController::class, 'exportKwitansi'])->name('invoices.exportKwitansi');
});
